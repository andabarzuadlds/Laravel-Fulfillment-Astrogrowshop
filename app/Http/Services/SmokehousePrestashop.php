<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\DB;
use App\Http\Contracts\PrestashopApiInterface;
use Throwable;
use PrestaShopWebservice;

class SmokehousePrestashop implements PrestashopApiInterface
{
    const PS_KEY = '1HXXVWVELSV8WE4H712DMMXEWE87RAUV';
    const PS_URL = 'https://smokehouse.cl';
    protected $psWebService;
    protected string $notificationEmailBody = '';
    protected string $logMessage = '';
    protected int $logBatchCode;

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

    public function postPrestashopOrder($productsToFulfill)
    {
		try {
            $this->setSyncBatchCode();
            $this->createCart($productsToFulfill);

        }catch (Throwable $e){
            echo "error: " . $e;
        }
    }

    protected function setSyncBatchCode(){
		$resultado = DB::select('SELECT batch_code FROM sync_orders_fulfillment ORDER BY id DESC LIMIT 1');

		$this->logBatchCode = count($resultado) > 0 ? ($resultado[0]->batch_code + 1) : 1;
	}

    protected function createCart($productsToFulfill)
    {
        $blankCartXml = $this->psWebService->get(array('url' => $this::PS_URL . '/api/carts?schema=blank'));
		$productCounter = 0;
        $totalPrice = 0;

        $cartFields = $blankCartXml->cart->children();

        // var_dump($cartFields);die;

        // $cartFields

        $cartFields->id_currency = $this->id_currency;
        $cartFields->id_lang = $this->id_lang;
        $cartFields->id_address_delivery = $this->id_address_delivery;
        $cartFields->id_address_invoice = $this->id_address_invoice;
        $cartFields->id_customer = $this->id_customer;
        $cartFields->id_shop = $this->id_shop;
        $cartFields->id_shop_group = 1;
        $cartFields->id_carrier = $this->id_carrier;
        
        foreach ($productsToFulfill as $productFulfill){
            
// print_r($productFulfill);die;
            //Tenemos que chequear que este producto (sku_producto) y esta orden (id_order) no está en el log de sincronización.
            //Este es un parche temporal a la duplicidad generada al colocar una orden, por alguna razón desconocida este proceso se triggea 2 veces en algunas ocasiones, resultando en órdenes duplicadas en el Prestashop DLDS
            if($this->isOrderProductAlreadySynced($productFulfill->sku_producto, $productFulfill->id_order) === false){
                
                $cartFields->associations->cart_rows->cart_row[$productCounter]->id_product = (int)$productFulfill->id_producto_dlds;
                $cartFields->associations->cart_rows->cart_row[$productCounter]->id_product_attribute = 0; 
                $cartFields->associations->cart_rows->cart_row[$productCounter]->id_address_delivery  = $this->id_address_delivery;
                $cartFields->associations->cart_rows->cart_row[$productCounter]->quantity = (int)$productFulfill->qty_producto_total;

                $this->notificationEmailBody .= 'SKU: <b>' . $productFulfill->sku_producto . '</b><br>'; 
                $this->notificationEmailBody .= 'ID en DLDS: <b>' . (int)$productFulfill->id_producto_dlds . '</b><br>'; 
                $this->notificationEmailBody .= 'Cantidad comprada: <b>' . $productFulfill->qty_producto_total . '</b><br>---<br>'; 
                $productCounter++;
            }

            if($productCounter > 0){
    
    			$createdCartXml = $this->psWebService->add([
    				'resource' => 'carts',
    				'postXml' => $blankCartXml->asXML(),
    			]);
    
    			//create order
    			$blankOrdersXml = $this->psWebService->get(array('url' => $this::PS_URL . '/api/orders?schema=blank'));
    
    			$orderFields = $blankOrdersXml->order->children();
    
    			$orderFields->id_address_delivery = $this->id_address_delivery;
    			$orderFields->id_address_invoice = $this->id_address_invoice;
    			$orderFields->id_cart = $createdCartXml->cart->id;
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
    
    			$productCounter = 0;
    
    			foreach ($productsToFulfill as $orderFulfill){
    				$prodID = (int)$orderFulfill['id_producto_dlds'];
    				$prodQty = (int)$orderFulfill['qty_producto_total'];
    				$priceReduction = null;
    				$totalProductPrice = 0;
    				
    				// echo "$sqlInsertSyncLog";die;
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
    
    				//Chequeamos si este producto se encuentra en más de 1 orden. 
    				//Hacemos esto para grabar en log de sincronización para todas las órdenes con este mismo producto.
    				if($orderFulfill['orders_with_this_product'] == 1){
    					$this->logMessage .= "($orderFulfill[id_order], '$orderFulfill[sku_producto]', $orderFulfill[qty_producto], $this->logBatchCode),";
    				}else{
    					
    					//si el producto se encuentra en más de 1 orden, entonces tenemos que loguear esas órdenes en el log también
    					$getOrdersForProduct = $this->mysqlConn->query("SELECT * FROM `orders_fulfillment` WHERE id_producto_dlds = $prodID");
    					// echo "SELECT * FROM `orders_fulfillment` WHERE id_producto_dlds = $prodID<br>";
    					
    					//Si hay órdenes esperando a ser sincronizadas, sincronizarlas
    					if($getOrdersForProduct->num_rows > 0){
    						while($row = $getOrdersForProduct->fetch_array(MYSQLI_ASSOC))
    						{
    							$this->logMessage .= "($row[id_order], '$row[sku_producto]', $row[qty_producto], $this->logBatchCode),";
    						}
    					}
    				}
    			}
    
    			$sqlInsertSyncLog = rtrim($this->logMessage, ',');
    			$queryInsertLog = "INSERT INTO sync_orders_fulfillment(id_order_astro, sku_producto, qty_producto, batch_code) 
    								VALUES $sqlInsertSyncLog";
    
    			$totalProducts = $productCounter;
    
    			$orderFields->total_paid = $totalPrice;
    			$orderFields->total_paid_real = $totalPrice;
    			$orderFields->total_paid_tax_incl = $totalPrice;
    			$orderFields->total_paid_tax_excl = $totalPrice;
    			$orderFields->total_products = $totalProducts; 
    			$orderFields->total_products_wt = $totalProducts;
    
    			//echo '<pre>' . var_export($orderFields, true) . '</pre>';die;
    
    			$createdOrderXml = $this->psWebService->add([
    				'resource' => 'orders',
    				'postXml' => $blankOrdersXml->asXML(),
    			]);	
                
                // $this->setOrderPlacedNewStatus($createdOrderXml->order->id, $webService);
                
    			$this->mysqlConn->query($queryInsertLog);
    			// $this->sendEmail($emailNotificationBody, 'Nueva compra en DLDS (productos fulfillment)');
    			echo '200 OK';
    			
            }else{
                echo '200 OK (no orders placed)';
            }
        }


    }

    protected function isOrderProductAlreadySynced($sku, $id_order){
		$resultado = DB::select("SELECT id FROM sync_orders_fulfillment WHERE sku_producto = '$sku' AND id_order_astro = $id_order");

        return (count($resultado) > 0 ? true : false);
	}
}