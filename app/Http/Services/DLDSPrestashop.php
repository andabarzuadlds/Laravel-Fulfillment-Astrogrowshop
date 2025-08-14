<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\DB;
use App\Http\Contracts\PrestashopApiInterface;
use App\Traits\SMTPService;
use Exception;
use Throwable;
use PrestaShopWebservice;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Http;

class DLDSPrestashop implements PrestashopApiInterface
{
	use SMTPService;

    const PS_KEY = 'UJWRIG3251NR2JNNH69589FABXGRPROD';
    const PS_URL = 'https://administracion.dlds.cl';
	const PS_WS_ORDER_STATUS = 2;
	const PS_WS_EMPLOYEE_ID = 65;
	const PS_WS_ORDER_CANCELLED = 6;

    protected $psWebService;
    protected $notificationEmailBody = 'reinaldo.tineo@dlds.cl';
    protected $logMessage = '';
    protected $logBatchCode;

    //Valores para la API del ambiente de prueba
	protected $id_customer = 2254; //Cristian Parada Fulfillment 
	protected $id_carrier = 404; //RETIRO EN 24 HORAS
	
	protected $id_currency = 2;
	protected $id_lang = 2;
	protected $id_address_delivery = 2849; //Marathon BODEGA
	protected $id_address_invoice = 2849;
	protected $id_shop = 1;
	protected $id_group = 26; //id de grupo "franquicias" dentro de DLDS
	
	protected $special_sku = [
		'1657139242059',
		'1657138981914',
		'1657139059485',
		'1657138793914',
		'WAXRCPR2T',
		'WAXRCPR4T',
		'WAXRCPR6T',
		'MLSLX9CMBLU',
		'MLSLX5CMYEL',
		'MLSLX9CMPRP',
		'MLSLX9CMPRP',
		'MLSLX9CMPRP',
		'MLSLX9CMPRP',
		'VPSZBVLCL',
		'SMSSAMNAUTOX100',
        'SMSSAMNFEMX100',
        'SMSSBBAUTOX100',
        'SMSSBBFEMX100',
        'SMSSMDAUTOX100',
        'SMSSMDFEMX100',
        'SMSSNLAUTOX100',
        'SMSSNLFEMX100',
        'SMSSWWFEMX100',
        'SMSSWWAUTOX100',
        'SMSSSSAUTOX100',
        'SMSSAK420FEMX100',
        'SMSSBCNCXXLAUTOX100',
        'SMSSBCNCXXLFEMX100',
        'SMSSAK420AUTOX100',
        'VPSZBMPNEG',
        'AYPZPBLKMA',
        'AYPZPCYHI',
        'AYPZPGRECH',
        'AYPZPALIDE',
        'AYPZPLEADE',
        'AYPZPFLADES',
        'AYPZPSKUDE',
        'AYPZPIROSTO',
        'AYPZPORAMA',
        'AYPZPHERSW',
        'AYPZPINSBTDO',
        'AYPZPBE125ML',
        'AYPZPMECHA',
        'AYPZPPIESACH',
        'AYPZPSANCR',
        'AYPZPCANDE',
        'VPPFPEAK',
        'SMFBLSD25AUTOX3',
        'AYPDAHDM10', 
        'AYPDAHDM12',
        'AYPDAHDM14',
        'AYPDAHDM15', 
        'AYPDAHDM16',
        'AYPDAHDM17',
        'AYPDAHDM18',
        'AYPDAHDM19',
        'STTCMERCMIX50LT',
        'STCNMERCOCO50LT',
        'INVPBXCPBMER240X240X200',
        'INVGHPPBXEPMER150X150X200',
        'INVGHPPBXEPMER240X120X200',
        'INVGHPPBXEPMER80X80X160',
        'INVCRPCPPMER60X60X160',
        'STBBMERALL50LT',
        'STBBMERALL20LT',
        'MSTSTHGR50LT',
        'WAXQNBM600K',
        'SMDSBGFAUTOX1',
        'SMDSBGFAUTOX2',
        'SMDSBGFAUTOX4',
        'SMDSBCOCRAUTOX1',
        'SMDSBCOCRAUTOX2',
        'SMDSBCOCRAUTOX4',
        'SMDSBGOHAZAUTOX1',
        'SMDSBGOHAZAUTOX2',
        'SMDSBGOHAZAUTOX4',
        'SMDSBMD3XLAUTOX1',
        'SMDSBMD3XLAUTOX2',
        'SMDSBMD3XLAUTOX4',
        'SMDSBWKAUTOX1',
        'SMDSBWKAUTOX2',
        'SMDSBWKAUTOX4',
        'SMDSBNYCAUTOX1',
        'SMDSBNYCAUTOX2',
        'SMDSBNYCAUTOX4',
        'SMDSBCAMAUTOX1',
        'SMDSBCAMAUTOX2',
        'SMDSBCAMAUTOX4',
        'SMDSBDEMOAUTOX1',
        'SMDSBDEMOAUTOX2',
        'SMDSBDEMOAUTOX4',
        'SMDSBPUROAUTOX1',
        'SMDSBPUROAUTOX2',
        'SMDSBPUROAUTOX4',
        'SMDSBPUROAUTOX12',
        'SMDSBDEAKAUTOX1',
        'SMDSBDEAKAUTOX2',
        'SMDSBDEAKAUTOX4',
        'SMDSBRERAAUTOX1',
        'SMDSBRERAAUTOX2',
        'SMDSBSKPCAUTOX1',
        'SMDSBSKPCAUTOX2',
        'SMDSBSKPCAUTOX4',
        'SMDSBMCBDAUTOX1',
        'SMDSBMCBDAUTOX2',
        'SMDSBPUMOAUTOX1',
        'SMDSBPUMOAUTOX4',
        'SMDSBMIXHVAUTOX4',
        'SMDSBMIXHVAUTOX8',
        'SMDSBMIXHVAUTOX14',
        'SMDSBMIXDMLAUTOX4',
        'SMDSBMIXDMLAUTOX8',
        'SMDSBMIXDMLAUTOX14',
        'SMDSBMIXLBAUTOX4',
        'SMDSBMIXLBAUTOX8',
        'SMDSBMIXLBAUTOX14',
        'SMDSBMIXLEYAUTOX4',
        'SMDSBMIXLEYAUTOX8',
        'SMDSBMIXLEYAUTOX14',
        'SMDSBDETANFEMX1',
        'SMDSBDETANFEMX2',
        'SMDSBCAMFEMX1',
        'SMDSBCAMFEMX4',
        'SMDSBPUROFEMX1',
        'SMDSBPUROFEMX2',
        'SMDSBPUROFEMX4',
        'SMDSBMCBDFEMX1',
        'SMDSBMCBDFEMX4',
        'SMDSBPUMOFEMX1',
        'SMDSBPUMOFEMX2',
        'SMDSBPUMOFEMX4',
        'SMDSBMIXVRFEMX4',
        'SMDSBMIXVRFEMX8',
        'SMDSBMIXLBFEMX4',
        'SMDSBMIXLBFEMX8',
        'SMDSBMIXLEYFEMX4',
        'SMDSBMIXLEYFEMX8',
        'SMDSBDEMOFVX1',
        'SMDSBDEMOFVX4',
        'SMDSBPRLFVX1',
        'SMDSBPRLFVX4',
        'SMDSBGOHAZFVX1',
        'SMDSBGOHAZFVX4',
        'SMDSBPUROFVX1',
        'SMDSBPUROFVX2',
        'SMDSBPUROFVX4',
        'SMDSBPUMOFVX1',
        'SMDSBPUMOFVX2',
        'SMDSBPUMOFVX4',
        'SMDSBMIXCRFAFVX4',
        'SMDSBMIXCRFAFVX8',
        'SMDSBMIXCRFAFVX14',
        'SMDSBMIXLEYFVX4',
        'SMDSBMIXLEYFVX8',
        'SMDSBMIXLEYFVX14'
	];
	
	// We need to connect to bsale and update it stock also
	protected $bsaleUrlApi = 'https://api.bsale.cl/v1/';
	protected $bsaleUrlReception = 'stocks/receptions.json';
	protected $bsaleUrlConsumption = 'stocks/consumptions.json';
	protected $bsaleUrlGetVariantId = 'variants.json?code=';
	protected $bsaleUrlProductStock = 'stocks.json';
	protected $bsaleApiHeaders = array(
		'access_token: 194083ab9d788a80fd0dfa605e2d92dd650173b7',
		'Accept: application/json',
		'Content-Type: application/json',
	);

	protected $bsaleBodegaFulfillmentId = 6;

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
			
			if(!$newOrderId){
			    
    			DB::table('orders_fulfillment_log')->whereIn('id_order_astro', $ordersIdsToFulfill)->update(['mensaje' => 'SIN STOCK']);
    			DB::table('asTr_orders')->whereIn('id_order', $ordersIdsToFulfill)->update(['id_order_dlds' => 'SIN STOCK']);
			    
			}else{
			    
    			foreach($ordersIdsToFulfill as $order_id){
        			    
    	    		DB::table('orders_fulfillment_log')
            		->where('id_order_astro', $order_id)
            		->update(['mensaje' => $newOrderId,
            							'id_order_third_party' => $newOrderId]);
            							
            	    DB::table('asTr_orders')
            		->where('id_order', $order_id)
            		->update(['id_order_dlds' => $newOrderId]);
        			    
        		}
    
    			echo "HTTP 201. Order Created";
			    
			}


			$carrier = DB::table('asTr_orders')->select('asTr_carrier.name as carrier')
			                        ->whereIn('id_order', $ordersIdsToFulfill)
			                        ->join('asTr_carrier', 'asTr_carrier.id_carrier', '=', 'asTr_orders.id_carrier')
			                        ->first(); 
			    
			DB::table('asTr_orders')->update(['carrier_name' => $carrier->carrier ]);
			

			//Quitar las filas de la tabla de orders_fulfillment porque ya se sincronizó correctamente todas las órdenes de la tabla
			$this->deleteOrdersThatWereFulfilled($ordersIdsToFulfill);

			$this->sendEmail($this->notificationEmailBody, 'Nueva compra vía API en DLDS');

        }catch (Throwable $e){
			
			//Rollback orders que estaban siendo procesadas.
			//Así quedan en cola para ser procesadas nuevamente cuando el proceso vuelva a correr
			DB::table('orders_fulfillment')->whereIn('id_order', $ordersIdsToFulfill)->update(['siendo_procesada' => false]);

			//Actualizamos el log para indicar que no fue un batch que se completó
			DB::table('orders_fulfillment_log')->whereIn('id_order_astro', $ordersIdsToFulfill)->update(['mensaje' => 'En espera']);
			
			
			DB::table('asTr_orders')->whereIn('id_order', $ordersIdsToFulfill)->update(['id_order_dlds' => 'En espera']);

			Log::error($e->getMessage());
			
// 			$this->sendEmail($e->getMessage() . ' <br> Line: ' . $e->getTraceAsString(), 'Se canceló la orden por falla en comunicación API');
			
			echo "ERORR MESSAGE: ".$e->getMessage();
			
			DB::table('orders_fulfillment')->delete();

        }
    }

    protected function setSyncBatchCode(){
		$resultado = DB::select('SELECT batch_code FROM orders_fulfillment_log ORDER BY id DESC LIMIT 1');

		$this->logBatchCode = count($resultado) > 0 ? ($resultado[0]->batch_code + 1) : 1;
	}
	
		/*
	* Revisa en el third party system tenga el estado correcto
	*/
	protected function verifyStatusOrderPlacedProductsAndStatus($productsToFulfill, $orderId){

			//Chequeamos que la orden tenga el estado correcto, si no lo tiene, se lo seteamos
			$orderPlaced = $this->psWebService->get(array('url' => $this::PS_URL . '/api/orders/' . $orderId));

			if($orderPlaced->order->current_state != $this::PS_WS_ORDER_STATUS){
				
				//tenemos que cambiar el estado de esta orden a CANCELADO
				$this->setOrderStatusOnThirdParty($orderId, $this::PS_WS_ORDER_CANCELLED);
			}
			
			$productsStockAvailable = [];
			foreach($orderPlaced->order->associations->order_rows->order_row as $orderRow){
			
				$product = $this->psWebService->get(array('url' => $this::PS_URL . '/api/stock_availables/' . (int)$orderRow->product_id));
				
				if((int)$orderRow->product_quantity > (int)$product->quantity){
				    
				    $this->setOrderStatusOnThirdParty($orderId, $this::PS_WS_ORDER_CANCELLED);
				    break;
				}
			}
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
	    
	    $date = Date('Y-m-d');
        $date .= " 00:00:00";
       
		$totalPrice = 0;
		$productCounter = 0;
		$blankOrdersXml = $this->psWebService->get(array('url' => $this::PS_URL . '/api/orders?schema=blank'));
		$orderFields = $blankOrdersXml->order->children();
		
		
		if(!$this->validateStock($productsToFulfill)) return false;
		
		error_log("Pasaron");

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
			
			$specific_price = \DB::connection('dlds')->table('r3pa_specific_price')->select('*')->where('id_product', $prodID)->where('to', ">=", $date)->where('from', "<=", $date)->orderBy('to', 'desc')->orderBy('reduction', 'desc')->first();
			    
		    $priceReduction = 0;
		    
		    if($specific_price){
		        
		        $priceReduction = (float)$specific_price->reduction;

		    }else{
		        
		        //we need to get the specific_prices resource for this product 
    			//because Astro has special prices from dlds
    			//we need to check if this product is selected to have a discount
    			$specificPriceRules = $this->psWebService->get(['resource' => 'specific_prices&filter[id_product]=' . $prodID]);
    
    			//we loop the prices rules and check the Group they are assigned to
    			foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
    				$specialPriceData = $this->psWebService->get(['resource' => 'specific_prices/' . $specificPriceIds['id']]);
    				
    				// we know that Group 5 is the Franquicias group in DLDS, which is the one that Astro belongs to
    				if((int)$specialPriceData->specific_price->id_group == $this->id_group){
    
    					 if((float)$specialPriceData->specific_price->reduction > $priceReduction && $specialPriceData->specific_price->to == "0000-00-00 00:00:00"){
                             //echo "GROUP REDUCTIONN ".$specialPriceData->specific_price->reduction."<br>";
                            //get the reduction assigned to this Group
    				       $priceReduction = (float)$specialPriceData->specific_price->reduction;
                        }
    				}
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
			
			
			try{
                
                // $this->decrementBsaleStock($productFulfill->sku_producto, $prodQty, $totalProductPrice);
                DB::table('productos_fulfillment')->where('sku', $productFulfill->sku_producto)->update(['costo' => $totalProductPrice]);
                
            }catch(Exception $ex){
                
            }
		}

		$this->setOrderTotals($orderFields, $totalPrice, $productCounter);
		
// 		if($this->validateStock($productsToFulfill)){
		    
		    $createdOrderXml = $this->psWebService->add([
    			'resource' => 'orders',
    			'postXml' => $blankOrdersXml->asXML(),
		    ]);	
		 

		    return (int) $createdOrderXml->order->id;
		
// 		}
		
		
// 		return false;


	}
	
	protected function validateStock($products){
	    
	    foreach($products as $productFulfill){
	        
	        $qty = $this->validate_stock_defontana($productFulfill->sku_producto);
				
    		if($qty < (int)$productFulfill->qty_producto_total){
                
                return false;
    		}
	    }
	    
	    
	    return true;
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
							
	    DB::table('asTr_orders')
		->whereIn('id_order', $ordersIdsToFulfill)
		->update(['id_order_dlds' => $newOrderId]);
	}

	protected function deleteOrdersThatWereFulfilled($ordersIdsToFulfill){
// 		DB::table('orders_fulfillment')->whereIn('id_order', $ordersIdsToFulfill)->delete();

        DB::table('orders_fulfillment')->delete();
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
	
	
	public function validate_stock_defontana($sku){
	    
       $data = '{"tokenPrestashop":"2CSYIMJTVIH5Y39PT1FM3YQWRGPESS7C","details":[{"sku":"'.$sku.'"}]}';
        
       $response = Http::withBody($data, 'text/plain')->post('https://defontana.sidekick.cl/webhooks/v1/getStock');
	   $response = json_decode($response);
	   
	 
	   
	   if($response && isset($response->response) && count($response->response)>0){
	       
	       error_log(json_encode($response));
	       
	       $qty = $response->response[0]->quantity;
	       
	   }else{
	       
	       $qty = 0;
	   }
	  
	   
   	    if(in_array($sku, $this->special_sku)){
		    
		    $qty = $qty - 0;
			
		}else{
		    
			$qty = $qty - 0;
		}
	   
    	echo "<br>STOCK: ".$qty;
    	
    	
       if($qty < 0) $qty = 0;
	   
	   
	   return $qty;
	   
	
	}
	
	
	public function getBsaleProductID($bsaleProductSKU) {
		$bsaleProductID = 000;
		
		echo "Bsale producto SKU: ".$bsaleProductSKU."<br>";
		
		//todo: temporal fix: sustituimos símbolo de más para poder usar como query param
		$bsaleProductSKU = str_replace('+', '%2B', $bsaleProductSKU);

		$session = curl_init($this->bsaleUrlApi . $this->bsaleUrlGetVariantId . $bsaleProductSKU);
		// echo $this->bsaleUrlApi . $this->bsaleUrlGetVariantId . $bsaleProductSKU; die;

		// echo "$this->bsaleUrlApi" . "$this->bsaleUrlGetVariantId" . "$bsaleProductSKU"; die;
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->bsaleApiHeaders);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);

		// Ejecuta cURL
		$response = curl_exec($session);

		$obj = json_decode($response);

		$code = curl_getinfo($session, CURLINFO_HTTP_CODE);

		// Cierra la sesión cURL
		curl_close($session);

		if ($code != 200) {
			$subject = 'Error en fulfillment: Api de Bsale no logró sacar el Product ID. Código: ' . $code;

			if ($code == 0) {
				$body = 'Bsale responde: El código no se encontró. Para SKU: ' . $bsaleProductSKU;
			} else {
				$body = 'Bsale reportó error: ' . $code . ' | Para SKU: ' . $bsaleProductSKU;
			}

// 			$sqlSyncInsertLog = "(0, 0, '$bsaleProductSKU', 0, 0, 0, 0, 'ERR $subject | $body', $batchCode)";

// 			$this->createSyncLog($sqlSyncInsertLog);
			//$this->sendEmail($body, $subject);
		} else {

            try{
                
                $bsaleProductID = $obj->items[0]->id;
                
            }catch(Exception $ex){
                
            }
			
		}

		return $bsaleProductID;
	}
	
	public function getbsaleDocuments() {
		
		//todo: temporal fix: sustituimos símbolo de más para poder usar como query param

		$session = curl_init($this->bsaleUrlApi . 'documents.json?limit=20');

		curl_setopt($session, CURLOPT_HTTPHEADER, $this->bsaleApiHeaders);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);

		// Ejecuta cURL
		$response = curl_exec($session);

		$obj = json_decode($response);

		$code = curl_getinfo($session, CURLINFO_HTTP_CODE);

		// Cierra la sesión cURL
		curl_close($session);


        try{
            
             var_dump($obj->items);
            
        }catch(Exception $ex){
            
        }
		
	}

	public function incrementBsaleStock($bsaleProductSKU, $stockToAdd, $productPrice = 0) {
	    
	    $productPrice = $this->getDLDSProductPrice($bsaleProductSKU);
	    
	    echo "<br>Incrementando stock bsale";

		//todo: temporal fix: sustituimos símbolo de más para poder usar como query param
		$bsaleProductSKU = str_replace('+', '%2B', $bsaleProductSKU);

		$bsaleProductID = $this->getBsaleProductID($bsaleProductSKU);

		//todo: entender cómo llegan a esta función valores con cero o menor a cero.
		if($stockToAdd > 0){

			$bodyReception = '{
				"document": "Actualización de stock de Defontana via Fulfillment",
				"officeId": ' . $this->bsaleBodegaFulfillmentId . ',
				"documentNumber": "99",
				"note": "Stock proveniente de Defontana vía Fulfillment API",
				"details": [
					{
					"quantity": ' . $stockToAdd . ',
					"variantId": ' . $bsaleProductID . ',
					"cost": ' . $productPrice . '
					}
				]
			}';
	
			// echo $bodyReception;die;
			// echo $this->bsaleUrlApi . $this->bsaleUrlReception; die;
			$session = curl_init($this->bsaleUrlApi . $this->bsaleUrlReception);
	
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_HTTPHEADER, $this->bsaleApiHeaders);
			curl_setopt($session, CURLOPT_POST, true);
			curl_setopt($session, CURLOPT_POSTFIELDS, $bodyReception);
			curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);
	
			$response = curl_exec($session);
			$code = curl_getinfo($session, CURLINFO_HTTP_CODE);
			curl_close($session);
	
			if ($code != '201') {
				$subject = 'Error en API de bsale (incrementBsaleStock) para SKU ' . $bsaleProductSKU;
				$body = 'Código del error: ' . $code . '<br>Respuesta de la API: <br>' . $response;
	
				// $sqlSyncInsertLog = "(0, 0, '$bsaleProductSKU', 0, 0, 0, 0, 'ERR $subject | $body', $batchCode)";
				// $this->createSyncLog($sqlSyncInsertLog);
	
				//$this->sendEmail($body, $subject);
			}
		}
	}

	public function decrementBsaleStock($bsaleProductSKU, $stockToDecrease, $productPrice) {

        Log::warning('Decrementando stock en bsale para sku');
        Log::warning($bsaleProductSKU);
        echo "<br>Decrementando stock bsale";
		//todo: temporal fix: sustituimos símbolo de más para poder usar como query param
		$bsaleProductSKU = str_replace('+', '%2B', $bsaleProductSKU);

		$bsaleProductID = $this->getBsaleProductID($bsaleProductSKU);

		//officeId 6 es Bodega Fulfillment
		$bodyConsumption = '{
			"note": "Salida de stock via API FULFILLMENT",
			"officeId": ' . $this->bsaleBodegaFulfillmentId . ',
			"details": [
				{
				"quantity": ' . $stockToDecrease . ',
				"variantId": ' . $bsaleProductID . '
				}
			]
		}';

		$session = curl_init($this->bsaleUrlApi . $this->bsaleUrlConsumption);

		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->bsaleApiHeaders);
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);

		curl_setopt($session, CURLOPT_POSTFIELDS, $bodyConsumption);
		$response = curl_exec($session);
		$code = curl_getinfo($session, CURLINFO_HTTP_CODE);
		curl_close($session);
		
		Log::warning('Decremento response');
		Log::warning($response);

		if ($code != '201') {
			$subject = 'Error en API de bsale (decrementBsaleStock) para SKU ' . $bsaleProductSKU . '<br>';
			$body = 'Código del error: ' . $code . '<br>Respuesta de la API: <br>' . $response;
			


// 			$sqlSyncInsertLog = "(0, 0, '$bsaleProductSKU', 0, 0, 0, 0, 'ERR $subject | $body', $batchCode)";
// 			$this->createSyncLog($sqlSyncInsertLog);

			//$this->sendEmail($body, $subject);
		}
	}
}