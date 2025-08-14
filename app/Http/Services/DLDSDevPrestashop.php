<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\DB;
use App\Http\Contracts\PrestashopApiInterface;
use App\Traits\SMTPService;
use Exception;
use Throwable;
use PrestaShopWebservice;
use Illuminate\Support\Facades\Log;

class DLDSDevPrestashop implements PrestashopApiInterface
{
	use SMTPService;

    const PS_KEY = '2KUN9AE821WGLVL81YT9K1YWC613Y19P';
    const PS_URL = 'http://desarrollodlds.delightdistribuidores.cl';
	const PS_WS_ORDER_STATUS = 2;
	const PS_WS_EMPLOYEE_ID = 65;
	const PS_WS_ORDER_CANCELLED = 6;

    protected $psWebService;
    protected $notificationEmailBody = '';
    protected $logMessage = '';
    protected $logBatchCode;

    //Valores para la API del ambiente de prueba
	protected $id_customer = 2254; //Cristian Parada Fulfillment 
	protected $id_carrier = 255; //RETIRO EN 24 HORAS
	
	protected $id_currency = 2;
	protected $id_lang = 2;
	protected $id_address_delivery = 2849; //Marathon BODEGA
	protected $id_address_invoice = 2849;
	protected $id_shop = 1;
	protected $id_group = 5; //id de grupo "franquicias" dentro de DLDS

    function __construct()
    {
        $this->psWebService = new PrestaShopWebservice($this::PS_URL, $this::PS_KEY, false);
    }

    public function postPrestashopOrder(array $productsToFulfill, array $ordersIdsToFulfill): void
    {
		
		ini_set('max_execution_time', 0);
		$this->setSyncBatchCode();

		try {
			//Creamos log inicial para marcar que este proceso comenzó (luego actualizaremos si pudo completarse o tuvo un error)
			$this->createLog($ordersIdsToFulfill, null, 'Procesando');

			//Creamos primero el carrito para luego hacer la orden
			$cartId = $this->createCart($productsToFulfill);

			//Ahora tenemos que crear la orden basada en el carrito que acabamos de crear
			$newOrderId = $this->createOrder($productsToFulfill, $cartId);

			$this->verifyOrderPlacedProductsAndStatus($productsToFulfill, $newOrderId);
			
			//Si pasa las verificaciones, actualizamos el log a éxito
			$this->updateLog($ordersIdsToFulfill, $newOrderId);

			echo "HTTP 201. Order Created";
			
			//Quitar las filas de la tabla de orders_fulfillment porque ya se sincronizó correctamente todas las órdenes de la tabla
			$this->deleteOrdersThatWereFulfilled($ordersIdsToFulfill);

			$this->sendEmail($this->notificationEmailBody, 'Nueva compra vía API en DLDS');

        }catch (Throwable $e){
			
			//Rollback orders que estaban siendo procesadas.
			//Así quedan en cola para ser procesadas nuevamente cuando el proceso vuelva a correr
			DB::table('orders_fulfillment')
				->whereIn('id_order', $ordersIdsToFulfill)
				->update(['siendo_procesada' => false]);

			//Actualizamos el log para indicar que no fue un batch que se completó
			DB::table('orders_fulfillment_log')
			->whereIn('id_order_astro', $ordersIdsToFulfill)
			->update(['mensaje' => 'ERROR']);

			$this->sendEmail($e->getMessage() . ' <br> Line: ' . $e->getTraceAsString(), 'Se canceló la orden por falla en comunicación API');

        }
    }

    protected function setSyncBatchCode(){
		$resultado = DB::select('SELECT batch_code FROM orders_fulfillment_log ORDER BY id DESC LIMIT 1');

		$this->logBatchCode = count($resultado) > 0 ? ($resultado[0]->batch_code + 1) : 1;
	}

	/*
	* Revisa en el third party system que la orden se colocó correctamente (tanto el status correcto como los productos y sus cantidades)
	*/
	protected function verifyOrderPlacedProductsAndStatus($productsToFulfill, $orderId){

			//Chequeamos que la orden tenga el estado correcto, si no lo tiene, se lo seteamos
			$orderPlaced = $this->psWebService->get(array('url' => $this::PS_URL . '/api/orders/' . $orderId));

			if($orderPlaced->order->current_state != $this::PS_WS_ORDER_STATUS){
				
				//tenemos que cambiar el estado de esta orden a CANCELADO
				$this->setOrderStatusOnThirdParty($orderId, $this::PS_WS_ORDER_CANCELLED);
			}

			//Creamos un arreglo simple con SKU => Cantidad (para el third party system).
			// Esto nos facilita comparar este arreglo con el arreglo original.
			$productsInThirdParty = [];
			foreach($orderPlaced->order->associations->order_rows->order_row as $orderRow){
				
				//Hacemos trim() porque la api de PS a veces devuelve espacios en los valores
				$productsInThirdParty[trim($orderRow->product_reference)] = (int)$orderRow->product_quantity;
			}

			//Ahora chequeamos los productos y sus cantidades
			foreach($productsToFulfill as $productFromOrigin){
				if(array_key_exists($productFromOrigin->sku_producto, $productsInThirdParty)){
					
					//El producto existe en el third party system, ahora chequeamos su cantidad
					if((int)$productFromOrigin->qty_producto_total !== $productsInThirdParty[$productFromOrigin->sku_producto]){
						
						//Cancelamos la orden
						$this->setOrderStatusOnThirdParty($orderId, $this::PS_WS_ORDER_CANCELLED);

						//Tiramos excepción
						throw new Exception('La orden está incompleta en el Third Party System. La cantidad para el producto ' . $productFromOrigin->sku_producto
														. ' es ' . $productFromOrigin->qty_producto_total
														. ' y lo que está en el otro sistema es: ' . $productsInThirdParty[$productFromOrigin->sku_producto]);
		
					}
				}else{
					
					//Cancelamos la orden
					$this->setOrderStatusOnThirdParty($orderId, $this::PS_WS_ORDER_CANCELLED);
					
					//Si no existe entonces la orden no se colocó completa en el third party system.
					// Necesitamos revertir este batch completo porque no fue acogido por el third party system correctamente
					throw new Exception('La orden está incompleta en el Third Party System, no tiene el producto: ' . $productFromOrigin->sku_producto);
				}
			}
	}

    protected function createCart($productsToFulfill)
    {
        $blankCartXml = $this->psWebService->get(array('url' => $this::PS_URL . '/api/carts?schema=blank'));
		$cartProductCounter = 0;
        
        $cartFields = $blankCartXml->cart->children();

		//Seteamos los valores XML del carrito
		$this->setCartFields($cartFields);

        foreach ($productsToFulfill as $productFulfill){

			$cartFields->associations->cart_rows->cart_row[$cartProductCounter]->id_product = (int)$productFulfill->id_producto_dlds;
			$cartFields->associations->cart_rows->cart_row[$cartProductCounter]->id_product_attribute = 0; 
			$cartFields->associations->cart_rows->cart_row[$cartProductCounter]->id_address_delivery  = $this->id_address_delivery;
			$cartFields->associations->cart_rows->cart_row[$cartProductCounter]->quantity = (int)$productFulfill->qty_producto_total;

			$this->setEmailNotificationBody($productFulfill);
			$cartProductCounter++;
		}
			
		if($cartProductCounter === 0) return null;

		$createdCartXml = $this->psWebService->add([
			'resource' => 'carts',
			'postXml' => $blankCartXml->asXML(),
		]);

		return $createdCartXml->cart->id;
	}

	protected function createOrder($productsToFulfill, $cartId)
	{
		$totalPrice = 0;
		$productCounter = 0;
		$blankOrdersXml = $this->psWebService->get(array('url' => $this::PS_URL . '/api/orders?schema=blank'));
		$orderFields = $blankOrdersXml->order->children();

		$this->setOrderFields($orderFields, $cartId);

		foreach ($productsToFulfill as $productFulfill){
			$prodID = (int)$productFulfill->id_producto_dlds;
			$prodQty = (int)$productFulfill->qty_producto_total;
			$priceReduction = null;
			$totalProductPrice = 0;
			
			$orderFields->associations->order_rows->order_row[$productCounter]->product_id = $prodID;
			$orderFields->associations->order_rows->order_row[$productCounter]->product_attribute_id = 0;
			$orderFields->associations->order_rows->order_row[$productCounter]->product_quantity = $prodQty;
			
			//get the price of each article, multiply it by product quantity and then add that to the total, so we get the overall total at the end
			$priceXML = $this->psWebService->get(['resource' => 'products/' . $prodID]);

			//we need to get the specific_prices resource for this product 
			//because Astro has special prices from dlds
			//we need to check if this product is selected to have a discount
			$specificPriceRules = $this->psWebService->get(['resource' => 'specific_prices&filter[id_product]=' . $prodID]);

			//we loop the prices rules and check the Group they are assigned to
			foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
				$specialPriceData = $this->psWebService->get(['resource' => 'specific_prices/' . $specificPriceIds['id']]);
				
				// we know that Group 5 is the Franquicias group in DLDS, which is the one that Astro belongs to
				if((int)$specialPriceData->specific_price->id_group == $this->id_group){

					//get the reduction assigned to this Group
					$priceReduction = (float)$specialPriceData->specific_price->reduction;
				}
			}

			$price = (float)$priceXML->product->price;

			if($priceReduction != null){
				$totalProductPrice = round(($price - ($price * $priceReduction)), 0, PHP_ROUND_HALF_UP);
			}else{
				$totalProductPrice = $price;
			}

			//add IVA to the price
			$priceWithIva = (round($totalProductPrice + ($totalProductPrice * 0.19), 0, PHP_ROUND_HALF_UP)) * $prodQty;
			
			//add product pice to total price for this order
			$totalPrice += $priceWithIva;
			
			//increment order product qty
			$productCounter++;
		}

		$this->setOrderTotals($orderFields, $totalPrice, $productCounter);

		$createdOrderXml = $this->psWebService->add([
			'resource' => 'orders',
			'postXml' => $blankOrdersXml->asXML(),
		]);	

		return (int) $createdOrderXml->order->id;
	}

    protected function isOrderProductAlreadySynced($sku, $id_order): bool
	{
		$resultado = DB::select("SELECT id FROM sync_orders_fulfillment WHERE sku_producto = '$sku' AND id_order_astro = $id_order");

        return (count($resultado) > 0 ? true : false);
	}

	protected function createLog($ordersIdsToFulfill, $newOrderId = null, $mensaje = '')
	{
		if($newOrderId === null) $newOrderId = 'null';

		//Obtenemos la data de las órdenes que se sincronizaron y agregamos estas al log
		$ordersToFulfillData = DB::select('SELECT * FROM orders_fulfillment WHERE id_order IN(' . implode(',', $ordersIdsToFulfill). ')');

		foreach($ordersToFulfillData as $orderFulfilled)
		{
			$this->logMessage .= "($orderFulfilled->id_order, '$orderFulfilled->sku_producto', $orderFulfilled->qty_producto, $newOrderId, $this->logBatchCode, '$mensaje'),";
		}

		$queryInsertLog = "INSERT INTO orders_fulfillment_log(id_order_astro, sku_producto, qty, id_order_third_party, batch_code, mensaje) 
								VALUES " . rtrim($this->logMessage, ',') ;

		DB::insert($queryInsertLog);
	}

	protected function updateLog($ordersIdsToFulfill, $newOrderId)
	{
		DB::table('orders_fulfillment_log')
		->whereIn('id_order_astro', $ordersIdsToFulfill)
		->update(['mensaje' => $newOrderId,
							'id_order_third_party' => $newOrderId]);
	}

	protected function deleteOrdersThatWereFulfilled($ordersIdsToFulfill){
		DB::table('orders_fulfillment')->whereIn('id_order', $ordersIdsToFulfill)->delete();
	}

	private function setCartFields(&$cartFields): void
	{
        $cartFields->id_currency = $this->id_currency;
        $cartFields->id_lang = $this->id_lang;
        $cartFields->id_address_delivery = $this->id_address_delivery;
        $cartFields->id_address_invoice = $this->id_address_invoice;
        $cartFields->id_customer = $this->id_customer;
        $cartFields->id_shop = $this->id_shop;
        $cartFields->id_shop_group = 1;
        $cartFields->id_carrier = $this->id_carrier;
	}

	private function setEmailNotificationBody($productFulfill): void
	{
		$this->notificationEmailBody .= 'SKU: <b>' . $productFulfill->sku_producto . '</b><br>'; 
		$this->notificationEmailBody .= 'ID en DLDS: <b>' . (int)$productFulfill->id_producto_dlds . '</b><br>'; 
		$this->notificationEmailBody .= 'Cantidad comprada: <b>' . $productFulfill->qty_producto_total . '</b><br>---<br>'; 
	}

	private function setOrderFields(&$orderFields, $cartId): void
	{
		$orderFields->id_address_delivery = $this->id_address_delivery;
		$orderFields->id_address_invoice = $this->id_address_invoice;
		$orderFields->id_cart = $cartId;
		$orderFields->id_currency = $this->id_currency;
		$orderFields->id_lang = $this->id_lang;
		$orderFields->id_customer = $this->id_customer;
		$orderFields->id_carrier = $this->id_carrier;
		$orderFields->module = 'ps_wirepayment'; 
		$orderFields->id_shop = $this->id_shop;
		$orderFields->id_shop_group = 1;
		$orderFields->payment = 'Pagos por transferencia bancaria';
		$orderFields->total_discounts = 0;
		$orderFields->total_discounts_tax_incl = 0;
		$orderFields->total_discounts_tax_excl = 0;
		$orderFields->conversion_rate = 1;
		$orderFields->total_shipping = 0;
		$orderFields->total_shipping_tax_incl = 0;
		$orderFields->total_shipping_tax_excl = 0;
		$orderFields->valid = 1;
		$orderFields->current_state = 2;
	}

	private function setOrderTotals(&$orderFields, $totalPrice, $totalProductsQty): void
	{
		$orderFields->total_paid = $totalPrice;
		$orderFields->total_paid_real = $totalPrice;
		$orderFields->total_paid_tax_incl = $totalPrice;
		$orderFields->total_paid_tax_excl = $totalPrice;
		$orderFields->total_products = $totalProductsQty; 
		$orderFields->total_products_wt = $totalProductsQty;
	}
	
	private function setOrderStatusOnThirdParty(int $orderId, int $newOrderStatus): void
	{
		$xmlOrder = $this->psWebService->get(array('url' => $this::PS_URL . '/api/order_histories?schema=blank'));
		$resources = $xmlOrder->order_history->children();

		$resources->id_order = $orderId;
		$resources->id_order_state = $newOrderStatus;
		$resources->id_employee = $this::PS_WS_EMPLOYEE_ID;

		$opt = [
			'resource' => 'order_histories',
			'postXml' => $xmlOrder->asXML(),
		];
		
		$this->psWebService->add($opt);
	}
}