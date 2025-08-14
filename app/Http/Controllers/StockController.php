<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Services\PrestashopService;
use App\Http\Contracts\PrestashopApiInterface;
use Illuminate\Support\Facades\Log;
use PrestaShopWebservice;

use Exception;
use Throwable;

use Illuminate\Support\Facades\Http;

class StockController extends Controller implements PrestashopApiInterface
{
    
    private $STOCK_DE_SEGURIDAD = 1;
    
    private $special_sku = [
        'VPPFHTKFPG',
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
	protected $psWebService;
	
	const PS_KEY = 'UJWRIG3251NR2JNNH69589FABXGRPROD';
    const PS_URL = 'https://administracion.dlds.cl';
	const PS_WS_ORDER_STATUS = 2;
	const PS_WS_EMPLOYEE_ID = 65;
	const PS_WS_ORDER_CANCELLED = 6;
	
	//Valores para la API del ambiente de prueba
	protected $id_customer = 2254; //Cristian Parada Fulfillment 
	protected $id_carrier = 404; //RETIRO EN 24 HORAS
	
	protected $id_currency = 2;
	protected $id_lang = 2;
	protected $id_address_delivery = 2849; //Marathon BODEGA
	protected $id_address_invoice = 2849;
	protected $id_shop = 1;
	protected $id_group = 26; //id de grupo "franquicias" dentro de DLDS
	
	
	private $THIRD_PARTY_BASE_URL = 'https://administracion.dlds.cl';
	private $THIRD_PARTY_API_KEY = 'UJWRIG3251NR2JNNH69589FABXGRPROD';
	private $ASTRO_DB_USER = 'astrogro_user002';
	private $ASTRO_API_KEY = 'XGKEJB8M26VDWWIVUZHJRCA45GWAECYK';
	private $ASTRO_BASE_URL = 'https://astrogrowshop.cl';
	private $ASTRO_DB_PW = '3JRXTd;eSCi]3JRXTd;eSCi]';
	private $ASTRO_DB_IP = '170.249.236.130';
	private $ASTRO_DB_NAME = 'astrogro_astr0032';
	
	// We need to connect to bsale and update it stock also
	protected $bsaleUrlApi = 'https://api.bsale.cl/v1/';
	protected $bsaleUrlReception = 'stocks/receptions.json';
	protected $bsaleUrlConsumption = 'stocks/consumptions.json';
	protected $bsaleUrlStock = 'stocks/receptions.json?limit=10&offset=0&officeid=6';
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
		    
		    
		    var_dump($productsToFulfill);
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
			                        ->where('id_order', $order_id)
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
			echo "<br><br>".$e->getTraceAsString();
			
			DB::table('orders_fulfillment')->delete();

        }
    }
    
    public function sync_stock(Request $request){
        
        ignore_user_abort(true);
        set_time_limit(0);
         
        //Chequeamos el token con el que viene el request para validarlo y elegir el Servicio correspondiente (token-based service)
        $requestInputs = $request->all();
        Log::warning('entró el request');
        
        //Si no existe el x-token no puede seguir
        if(!array_key_exists('x-token', $requestInputs)){
            die('No está validado');
        }

        //Instanciamos el servicio de Prestashop basado en el x-token que se recibió
        $apiService = PrestashopService::getApiServiceBasedOnToken($requestInputs['x-token']);
        if(is_null($apiService)){

            //TODO: return http status code
            die('No está validado');
        }
        
        
        //Revisamos tabla de order fulfillment para ver si hay orders en cola, si hay, nos traemos los ids de órdenes únicos
        $products_to_sync = DB::table('productos_fulfillment')->select('*')
                                                              ->where('to_sync', 1)
                                                              ->orderBy('id', 'desc')
                                                              ->get();
        
        if(count($products_to_sync) <= 0) die('Nada para sincronizar');
        
        foreach( $products_to_sync as $product){
            
        	$productosFulfillment[$product->sku] = array(
				'astro_id' => $product->astro_product_id,
				'dlds_id' => $product->dlds_product_id
			);
        }
        
        // loop the ids and get the stock for each fulfillment product
		foreach ($productosFulfillment as $sku => $product) {
		  
			$thirdPartyStock = 0;
			$nuevoStock = 0;
			
			try {
			    
			    $thirdPartyStock = $this->get_stock_sku($sku);
			    
			    if($thirdPartyStock == -1){
			        //No existe aun en la nueva tabla
			        
			        $product_dlds = \DB::connection('dlds')->table('r3pa_stock_available')->select('*')->where('id_product', $product['dlds_id'])->first();
			        
			        
			        
			        if($product_dlds){
			           
			            
			            $thirdPartyStock = $this->validate_stock($sku, $product_dlds->quantity);
			            
			        }else{
			            
			            $thirdPartyStock = 0;
			        }
			        
			    } 
			    
			    
			    if($thirdPartyStock){
			        
			        if(!$this->check_if_product_combination($sku)){
                    
                        
                        $productoStockAvailable = DB::table('asTr_stock_available')->select('id_stock_available', 'quantity')->where('id_product', $product['astro_id'])->first();
        				
        				if($productoStockAvailable){
        				    
        				    $astroStock = $productoStockAvailable->quantity;
	
            				$this->update_stock($astroStock, $thirdPartyStock, false, $product['astro_id'], $sku);
        				    
        				}else{
        				    
        				    $this->update_stock(0, $thirdPartyStock, false, $product['astro_id'], $sku);
        				    
        				    
        				    
        				}
        				
                        
                    }else{
                        
                        $product_combination = $this->get_product_combination($sku);
                        
                        $id_product_attribute = $product_combination->id_product_attribute;
                        
                        $productoStockAvailable = DB::table('asTr_stock_available')->select('id_stock_available', 'quantity')->where('id_product_attribute', $id_product_attribute)->first();
                    
    				    
    				    if($productoStockAvailable){
    				        
    				        $astroStock = $productoStockAvailable->quantity;
    				        
            				$product['astro_id'] = $product_combination->id_product;
            				
            				$this->update_stock($astroStock, $thirdPartyStock, true, $product['astro_id'], $sku);
    				    }
        				
                    }
			        
			        
			    }else{
			        
			        $this->add_product_tienda($sku);
			        
			    }
			    
                
            }catch(Throwable $ex){
                
                error_log("Error");
                error_log($ex->getMessage());
            }
			
		}
                                                    
    }
    
    public function sync_stock_task(){
        
        //Revisamos tabla de order fulfillment para ver si hay orders en cola, si hay, nos traemos los ids de órdenes únicos
        $products_to_sync = DB::table('productos_fulfillment')->select('*')
                                                            //   ->where('to_sync', 1)
                                                              ->orderBy('id', 'desc')
                                                              ->limit(200)
                                                              ->get();
        
        if(count($products_to_sync) <= 0) die('Nada para sincronizar');
        
        foreach( $products_to_sync as $product){
            
        	$productosFulfillment[$product->sku] = array(
				'astro_id' => $product->astro_product_id,
				'dlds_id' => $product->dlds_product_id
			);
        }
        
        // loop the ids and get the stock for each fulfillment product
		foreach ($productosFulfillment as $sku => $product) {
		  
			$thirdPartyStock = 0;
			$nuevoStock = 0;
			
			try {
			    
			    $thirdPartyStock = $this->get_stock_sku($sku);
			    
			    if($thirdPartyStock == -1){
			        //No existe aun en la nueva tabla
			        
			        $product_dlds = \DB::connection('dlds')->table('r3pa_stock_available')->select('*')->where('id_product', $product['dlds_id'])->first();
			        
			        
			        
			        if($product_dlds){
			           
			            
			            $thirdPartyStock = $this->validate_stock($sku, $product_dlds->quantity);
			            
			        }else{
			            
			            $thirdPartyStock = 0;
			        }
			        
			    } 
			    
			    
			    if($thirdPartyStock){
			        
			        if(!$this->check_if_product_combination($sku)){
                    
                        
                        $productoStockAvailable = DB::table('asTr_stock_available')->select('id_stock_available', 'quantity')->where('id_product', $product['astro_id'])->first();
        				
        				if($productoStockAvailable){
        				    
        				    $astroStock = $productoStockAvailable->quantity;
	
            				$this->update_stock($astroStock, $thirdPartyStock, false, $product['astro_id'], $sku);
        				    
        				}else{
        				    
        				    $this->update_stock(0, $thirdPartyStock, false, $product['astro_id'], $sku);
        				    
        				}
        				
                        
                    }else{
                        
                        $product_combination = $this->get_product_combination($sku);
                        
                        $id_product_attribute = $product_combination->id_product_attribute;
                        
                        $productoStockAvailable = DB::table('asTr_stock_available')->select('id_stock_available', 'quantity')->where('id_product_attribute', $id_product_attribute)->first();
                    
    				    
    				    if($productoStockAvailable){
    				        
    				        $astroStock = $productoStockAvailable->quantity;
    				        
            				$product['astro_id'] = $product_combination->id_product;
            				
            				$this->update_stock($astroStock, $thirdPartyStock, true, $product['astro_id'], $sku);
    				    }
        				
                    }
			        
			        
			    }else{
			        
			        //$this->add_product_tienda($sku);
			        
			    }
			    
                
            }catch(Throwable $ex){
                
                error_log("Error");
                error_log($ex->getMessage());
                
                echo $ex->getMessage();
            }
			
		}
                                                    
    }
    
    
    public function sync_stock_test(){
    
    ignore_user_abort(true);
    set_time_limit(0);
    $products_to_sync = DB::table('productos_fulfillment')->select('*')
                                                //   ->where('to_sync', 1)
                                                  ->orderBy('id', 'desc')
                                                  ->get();
                                                  
        if(count($products_to_sync) <= 0) die('Nada para sincronizar');
        
        foreach( $products_to_sync as $product){
            
        	$productosFulfillment[$product->sku] = array(
				'astro_id' => $product->astro_product_id,
				'dlds_id' => $product->dlds_product_id
			);
        }
        
        // loop the ids and get the stock for each fulfillment product
		foreach ($productosFulfillment as $sku => $product) {
		    
		   
		    $this->sync_stock_by_sku($sku);
		}
        
    }
    
    public function sync_stock_defontana(){
        
         $products_to_sync = DB::table('productos_fulfillment')->select('*')
                                                              ->where('to_sync', 1)
                                                              ->orderBy('id', 'desc')
                                                              ->get();
        
        if(count($products_to_sync) <= 0) die('Nada para sincronizar');
        
        foreach( $products_to_sync as $product){
            
        	$productosFulfillment[$product->sku] = array(
				'astro_id' => $product->astro_product_id,
				'dlds_id' => $product->dlds_product_id
			);
        }
        
    }
    
    public function check_if_product_combination($sku){
        $productoFulfillment = DB::table('asTr_product_attribute')->select('reference', 'id_product_attribute')
                                                                  ->where('reference', $sku)
                                                                  ->first();

        
        if($productoFulfillment) return  $productoFulfillment->id_product_attribute;
	    
	    return 0;

    }
    
	public function get_product_combination($sku_id){
	    
	    $productoFulfillment = DB::table('asTr_product_attribute')->select('*')->where('reference', $sku_id)->first();
	    
	    
	    if($productoFulfillment) return $productoFulfillment;
	    
	    return null;
	    
	}
	
	public function update_stock($astroStock, $thirdPartyStock, $is_combination, $product_id, $sku){
	
		//$thirdPartyStockWithSecuredStock = ($thirdPartyStock - $this->STOCK_DE_SEGURIDAD);
        $thirdPartyStockWithSecuredStock = ($thirdPartyStock);
		
		if ($thirdPartyStock == 0 && $astroStock == 0) {
			echo "$product_id no se sincronizó, stock está en cero en ambos endpoints.<br>$thirdPartyStock == $astroStock";
			
 			// $this->add_product_tienda($sku);
// 			return;
		}
		
		if ($thirdPartyStockWithSecuredStock != $astroStock) {
		    
    		if ($thirdPartyStockWithSecuredStock <= 0) {
				// we set the stock available to zero so it doesn't get a negative value
				$nuevoStock = 0;
				$emailMsg = '<b>STOCK ACTUALIZADO A CERO EN BONGLAB PS</b>: stock generado con stock de seguridad daba menor a cero: ' . $thirdPartyStockWithSecuredStock;
                
                try{
                    
                    if(!$is_combination){
    			        
    			        $res = DB::table('asTr_stock_available')->where('id_product', $product_id)->update(['quantity' => DB::raw($nuevoStock)]);
    			        
    			    }else{
    			        
    			         $res = DB::table('asTr_stock_available')->where('id_product_attribute', $product_id)->update(['quantity' => DB::raw($nuevoStock)]);
    			         
    			         
    	
			        }
                    
                }catch(Throwable $ex){
                    
                    echo "<br>Error actualizando stock en producto ".$product_id."<br>";
                }

				


			} else {

				$nuevoStock = $thirdPartyStockWithSecuredStock;
				
				echo "Stock mayor a cero<br>";
				
				echo "IS COMBINATION: ".$is_combination."<br>";

				if(!$is_combination){
			        
			        
			        $res = DB::table('asTr_stock_available')->where('id_product', $product_id)->update(['quantity' => DB::raw($nuevoStock)]);
			        
			        
			    }else{
			        
			         $res = DB::table('asTr_stock_available')->where('id_product_attribute', $product_id)->update(['quantity' => DB::raw($nuevoStock)]);
			         echo "Update stock of product combination id: ".$product_id." - ".$nuevoStock."<br>";
			         
			         echo "RES: ".$res."<br>";
			         
	
			    }
			}
			
			echo "NUEVO STOCK para producto ".$sku.": ".$nuevoStock."<br><br>";
		    
		}else{
		    
		    //echo "$product_id no se sincronizó, stock estuvo igual. $thirdPartyStockWithSecuredStock === $astroStock<br><br>";
		    
		}
		
		

	}
	
	public function update_stock_delight($astroStock, $thirdPartyStock, $is_combination, $product_id, $sku){
	
		//$thirdPartyStockWithSecuredStock = ($thirdPartyStock - $this->STOCK_DE_SEGURIDAD);
        $thirdPartyStockWithSecuredStock = ($thirdPartyStock);
		
		if ($thirdPartyStock == 0 && $astroStock == 0) {
			echo "$product_id no se sincronizó, stock está en cero en ambos endpoints.<br>$thirdPartyStock == $astroStock";
			
 			$this->add_product_tienda($sku);
// 			return;
		}
		
		if ($thirdPartyStockWithSecuredStock != $astroStock) {
		    
    		if ($thirdPartyStockWithSecuredStock <= 0) {
				// we set the stock available to zero so it doesn't get a negative value
				$nuevoStock = 0;
				$emailMsg = '<b>STOCK ACTUALIZADO A CERO EN BONGLAB PS</b>: stock generado con stock de seguridad daba menor a cero: ' . $thirdPartyStockWithSecuredStock;
                
                try{
                    
                    if(!$is_combination){
    			        
    			        $res = \DB::connection('delight')->table('psjk_stock_available')->where('id_product', $product_id)->update(['quantity' => DB::raw($nuevoStock)]);
    			        
    			    }else{
    			        
    			         $res = \DB::connection('delight')->table('psjk_stock_available')->where('id_product_attribute', $product_id)->update(['quantity' => DB::raw($nuevoStock)]);
    			         
    			         
    	
			        }
                    
                }catch(Throwable $ex){
                    
                    echo "<br>Error actualizando stock en producto ".$product_id."<br>";
                }

				


			} else {

				$nuevoStock = $thirdPartyStockWithSecuredStock;
				
				echo "Stock mayor a cero<br>";
				
			        
		         $res = \DB::connection('delight')->table('psjk_stock_available')->where('id_product_attribute', $product_id)->update(['quantity' => DB::raw($nuevoStock)]);
		         echo "Update stock of product combination id: ".$product_id." - ".$nuevoStock."<br>";
		         
		         echo "RES: ".$res."<br>";
			         
			}
			
			echo "NUEVO STOCK para producto ".$sku.": ".$nuevoStock."<br><br>";
		    
		}else{
		    
		    //echo "$product_id no se sincronizó, stock estuvo igual. $thirdPartyStockWithSecuredStock === $astroStock<br><br>";
		    
		}
		
		

	}
	
	public function sync_stock_by_sku($sku){
        
        ignore_user_abort(true);
        set_time_limit(0);
         

        //Instanciamos el servicio de Prestashop basado en el x-token que se recibió
        
        
        //Revisamos tabla de order fulfillment para ver si hay orders en cola, si hay, nos traemos los ids de órdenes únicos
        $products_to_sync = DB::table('productos_fulfillment')->select('*')
                                                              ->where('sku', $sku)
                                                              ->get();
        
        if(count($products_to_sync) <= 0) die('Nada para sincronizar');
        
        foreach( $products_to_sync as $product){
            
        	$productosFulfillment[$product->sku] = array(
				'astro_id' => $product->astro_product_id,
				'dlds_id' => $product->dlds_product_id
			);
        }
        
        // loop the ids and get the stock for each fulfillment product
		foreach ($productosFulfillment as $sku => $product) {
		    
			try {
				//TODO: esto es provisorio: necesitamos un stock de seguridad distinto para estos prods
				
			    echo "<br>SKU: ".$sku."<br>";
			    echo "<br>ID: ".$product['astro_id']."<br>";
				
				if(in_array($sku, $this->special_sku)){
				    
					$this->STOCK_DE_SEGURIDAD = 0;
					// echo 'seteado stock de seguidad nuevo para ' . $sku . '<br>'; 
				}else{
					$this->STOCK_DE_SEGURIDAD = 1;
				}
			    
				$emailMsg = '';
				$emailNotifBody = '';
				$thirdPartyStock = 0;
				$stockToAdd = 0;
				$nuevoStock = 0;
				$stockToDecrease = 0;
                
				// Call to product api first, then call stock_availables 
				// Prestashop's products id differs from the 'product' resource to the 'stock_availables' resource, 
				// both of them take the $id parameter, but it's somehow different in some products (?)
				
				try {
				    
                    $web_service = new PrestaShopWebservice(
                        $this->THIRD_PARTY_BASE_URL,
                        $this->THIRD_PARTY_API_KEY,
                        false
                    );
                    
                    $web_service_astro = new PrestaShopWebservice(
                        $this->ASTRO_BASE_URL,
                        $this->ASTRO_API_KEY,
                        false
                    );
                    
                    $opt = ["resource" => "products"];
                                        
                    try {
                        $thirdPartyXML = $web_service->get([
                            "url" =>
                            $this->THIRD_PARTY_BASE_URL."/api/products/".$product['dlds_id'],
                        ]);
                            // whatever you want to do if exception is thrown
                    } catch (\Exception $e) {
                            // whatever you want to do if exception is thrown
                            echo $e->getMessage();
                            continue;
                        
                    }
                    
                    $productIdForStock = (int)$thirdPartyXML->product->associations->stock_availables->stock_available->id;
                    echo "Product ID for Stock DLDS: ".$productIdForStock."<br>";
                    
 
                
                    try {
                        
                        $thirdPartyXML = $web_service->get([
                            "url" =>
                            $this->THIRD_PARTY_BASE_URL ."/api/stock_availables/".$productIdForStock,
                        ]);
                        
                    } catch (\Exception $e) {
                        // whatever you want to do if exception is thrown
                        
                        continue;
                    }
                    
                    if (isset($thirdPartyXML->stock_available->quantity)) {
    					$thirdPartyStock = (int)$thirdPartyXML->stock_available->quantity;
    				}
    				
    			    $id_product_attribute = 0;
                
                    if(!$this->check_if_product_combination($sku)){
                        
                        try {
                                $astroXMLProduct = $web_service_astro->get([
                                        "url" =>
                                        $this->ASTRO_BASE_URL."/api/products/".$product['astro_id'],
                                ]);
                        } catch (\Exception $e) {
                            // whatever you want to do if exception is thrown
                            
                            continue;
                        }
                        
  
                        
        				$productIdForStock = (int)$astroXMLProduct->product->associations->stock_availables->stock_available->id;
        				echo "Product ID for Stock ASTRO: ".$productIdForStock."<br>";

                        
                        try {
                                      				
            				$astroXML = $web_service_astro->get([
                                    "url" =>
                                    $this->ASTRO_BASE_URL."/api/stock_availables/".$productIdForStock,
                            ]);
                            
                        } catch (\Exception $e) {
                            // whatever you want to do if exception is thrown
                            
                            continue;
                        }
                        
                            
        				
        				// print_r($astroXML);die;
        				// $astroStock = $astroXML->stock_available->quantity == 0 ? 0 : $astroXML->stock_available->quantity;
        				$astroStock = (int) $astroXML->stock_available->quantity;
        			
        				echo "ASTRO STOCK: ".$astroStock;
        				
        				$this->update_stock($astroStock, $thirdPartyStock, false, $product['astro_id'], $sku);
                        
                    }else{
                        
                        $product_combination = $this->get_product_combination($sku);
                        
                        $id_product_attribute = $product_combination->id_product_attribute;
                        
                        $productoStockAvailable = DB::table('asTr_stock_available')->select('id_stock_available', 'quantity')->where('id_product_attribute', $id_product_attribute)->first();
                        
    				    $productIdForStock = 0;
    				    
    				    if($productoStockAvailable){
    				        $productIdForStock = $productoStockAvailable->id_stock_available;
    				        $astroStock = $productoStockAvailable->quantity;
    				        
            				//$product['astro_id'] = $product_combination->id_product;
            				
            				$this->update_stock($astroStock, $thirdPartyStock, true, $product['astro_id'], $sku);
    				    }
        				
                    }
                        
                    
                }catch(Throwable $ex){
                    
                    error_log("Error");
                    error_log($ex->getMessage());
                }
                
	
            
				
			} catch (PrestaShopWebserviceException $ex) {

				// $sqlSyncInsertLog .= "($product[astro_id], $product[dlds_id], '$sku', $astroStock, $thirdPartyStock, $astroStock, $stockToDecrease, 'ERR  PrestaShopWebserviceException (look for email)', $batchCode),";

				$subject = 'Error en la API de Prestashop';
				$body = 'ID de producto de Bonglab: ' . $product['astro_id'] .
					'<br>ID de producto de DLDS: ' . $product['dlds_id'] .
					'<br>La respuesta de la API fue: <br />' . $ex;

				// $this->sendEmail($body, $subject);
			}
			
			
		}
                                                    
    }
    
    public function sync_stock_db(Request $request){
        
        ignore_user_abort(true);
        set_time_limit(0);
         
        //Chequeamos el token con el que viene el request para validarlo y elegir el Servicio correspondiente (token-based service)
        $requestInputs = $request->all();
        Log::warning('entró el request');
        //Si no existe el x-token no puede seguir
        if(!array_key_exists('x-token', $requestInputs)){
            die('No está validado');
        }

        //Instanciamos el servicio de Prestashop basado en el x-token que se recibió
        $apiService = PrestashopService::getApiServiceBasedOnToken($requestInputs['x-token']);
        if(is_null($apiService)){

            //TODO: return http status code
            die('No está validado');
        }
        
        
        //Revisamos tabla de order fulfillment para ver si hay orders en cola, si hay, nos traemos los ids de órdenes únicos
        $products_to_sync = DB::table('productos_fulfillment')->select('*')
                                                              ->where('activo', 1)
                                                              ->orderBy('id', 'desc')
                                                              ->get();
        
        if(count($products_to_sync) <= 0) die('Nada para sincronizar');
        
        foreach( $products_to_sync as $product){
            
        	$productosFulfillment[$product->sku] = array(
				'astro_id' => $product->astro_product_id,
				'dlds_id' => $product->dlds_product_id
			);
        }
        
        // loop the ids and get the stock for each fulfillment product
		foreach ($productosFulfillment as $sku => $product) {
		    
			try {
				//TODO: esto es provisorio: necesitamos un stock de seguridad distinto para estos prods
				
			    echo "<br>SKU: ".$sku."<br>";
				
				if(in_array($sku, $this->special_sku)){
				    
					$this->STOCK_DE_SEGURIDAD = 0;
					// echo 'seteado stock de seguidad nuevo para ' . $sku . '<br>'; 
				}else{
					$this->STOCK_DE_SEGURIDAD = 1;
				}
			    
				$emailMsg = '';
				$emailNotifBody = '';
				$thirdPartyStock = 0;
				$stockToAdd = 0;
				$nuevoStock = 0;
				$stockToDecrease = 0;
                
				// Call to product api first, then call stock_availables 
				// Prestashop's products id differs from the 'product' resource to the 'stock_availables' resource, 
				// both of them take the $id parameter, but it's somehow different in some products (?)
				
				try {
				    
                    $web_service = new PrestaShopWebservice(
                        $this->THIRD_PARTY_BASE_URL,
                        $this->THIRD_PARTY_API_KEY,
                        false
                    );
                    
                    $web_service_astro = new PrestaShopWebservice(
                        $this->ASTRO_BASE_URL,
                        $this->ASTRO_API_KEY,
                        false
                    );
                    
                    $opt = ["resource" => "products"];
                                        
                    try {
                        $thirdPartyXML = $web_service->get([
                            "url" =>
                            $this->THIRD_PARTY_BASE_URL."/api/products/".$product['dlds_id'],
                        ]);
                        
                        $product = \DB::connection('dlds')->table('r3pa_stock_available')->select('*')->where('id_product', $prodID)->first();
                        
                        
                            // whatever you want to do if exception is thrown
                    } catch (\Exception $e) {
                            // whatever you want to do if exception is thrown
                            
                            continue;
                        
                    }
                    
                    $productIdForStock = (int)$thirdPartyXML->product->associations->stock_availables->stock_available->id;
                    echo "Product ID for Stock DLDS: ".$productIdForStock."<br>";
                    
 
                
                    try {
                        $thirdPartyXML = $web_service->get([
                            "url" =>
                            $this->THIRD_PARTY_BASE_URL ."/api/stock_availables/".$productIdForStock,
                        ]);
                    } catch (\Exception $e) {
                        // whatever you want to do if exception is thrown
                        
                        continue;
                    }
                    
                    if (isset($thirdPartyXML->stock_available->quantity)) {
    					$thirdPartyStock = (int)$thirdPartyXML->stock_available->quantity;
    				}
    				
    			    $id_product_attribute = 0;
                
                    if(!$this->check_if_product_combination($sku)){
                        
                        try {
                                $astroXMLProduct = $web_service_astro->get([
                                        "url" =>
                                        $this->ASTRO_BASE_URL."/api/products/".$product['astro_id'],
                                ]);
                        } catch (\Exception $e) {
                            // whatever you want to do if exception is thrown
                            
                            continue;
                        }
                        
  
                        
        				$productIdForStock = (int)$astroXMLProduct->product->associations->stock_availables->stock_available->id;
        				echo "Product ID for Stock ASTRO: ".$productIdForStock."<br>";

                        
                        try {
                                      				
            				$astroXML = $web_service_astro->get([
                                    "url" =>
                                    $this->ASTRO_BASE_URL."/api/stock_availables/".$productIdForStock,
                            ]);
                            
                        } catch (\Exception $e) {
                            // whatever you want to do if exception is thrown
                            
                            continue;
                        }
                        
                            
        				
        				// print_r($astroXML);die;
        				// $astroStock = $astroXML->stock_available->quantity == 0 ? 0 : $astroXML->stock_available->quantity;
        				$astroStock = (int) $astroXML->stock_available->quantity;
        			
        				
        				$this->update_stock($astroStock, $thirdPartyStock, false, $product['astro_id'], $sku);
                        
                    }else{
                        
                        $product_combination = $this->get_product_combination($sku);
                        
                        $id_product_attribute = $product_combination->id_product_attribute;
                        
                        $productoStockAvailable = DB::table('asTr_stock_available')->select('id_stock_available', 'quantity')->where('id_product_attribute', $id_product_attribute)->first();
                        
    				    $productIdForStock = 0;
    				    
    				    if($productoStockAvailable){
    				        $productIdForStock = $productoStockAvailable->id_stock_available;
    				        $astroStock = $productoStockAvailable->quantity;
    				        
            				//$product['astro_id'] = $product_combination->id_product;
            				
            				$this->update_stock($astroStock, $thirdPartyStock, true, $product['astro_id'], $sku);
    				    }
        				
                    }
                        
                    
                }catch(Throwable $ex){
                    
                    error_log("Error");
                    error_log($ex->getMessage());
                }
                
	
            
				
			} catch (PrestaShopWebserviceException $ex) {

				// $sqlSyncInsertLog .= "($product[astro_id], $product[dlds_id], '$sku', $astroStock, $thirdPartyStock, $astroStock, $stockToDecrease, 'ERR  PrestaShopWebserviceException (look for email)', $batchCode),";

				$subject = 'Error en la API de Prestashop';
				$body = 'ID de producto de Bonglab: ' . $product['astro_id'] .
					'<br>ID de producto de DLDS: ' . $product['dlds_id'] .
					'<br>La respuesta de la API fue: <br />' . $ex;

				// $this->sendEmail($body, $subject);
			}
			
			
		}
                                                    
    }
    
    public function get_stock_defontana($sku){

	   
	   
	   try{
	       
	       	    
           $data = '{"tokenPrestashop":"2CSYIMJTVIH5Y39PT1FM3YQWRGPESS7C","details":[{"sku":"'.$sku.'"}]}';
            
           $response = Http::withBody($data, 'text/plain')->post('https://defontana.sidekick.cl/webhooks/v1/getStock');
    	   $response = json_decode($response);
	       
            $qty = $response->response[0]->quantity;
            
            $qty = $this->validate_stock($sku, $qty);
            
            return $qty;
	       
	   }catch(\Exception $ex){
	    
	    return 0;
	       
	   }
	  
	
	}
	
	public function validate_stock($sku, $stock){
	    
       if(in_array($sku, $this->special_sku)){
            
            $qty = $stock - 0;
        	
        }else{
            
        	$qty = $stock - 3;
        }
        
    
        if($qty < 0) $qty = 0;
        
        
        return $qty;
	    
	}
	
	public function add_product($sku){
	    
	   $producto = [];
	       
       $producto['sku'] = $sku;
       $producto['dlds_id'] = $this->getThirdPartyIdByReference($sku);
       $producto['astro_id'] = $this->getIdByReferenceOrCheckIfCombination($sku);
       
       $producto_fulfillment = DB::table('productos_fulfillment')
                                        ->where('sku', $sku)
                                        ->limit(1)
                                        ->get();
                                        
       if(count($producto_fulfillment)> 0 ){
           
          echo "<br>El producto con sku ".$sku." ya es fulfillment. <br>";
          
          
       }else{
           
           echo "<br>Producto con SKU: ".$sku." No es fulfillment. Agregando...<br>";
           
           if($producto['dlds_id'] && $producto['astro_id']){
               
              $res =  DB::table('productos_fulfillment')->insert([
                    'sku' => $sku,
                    'costo' => 0,
                    'dlds_product_id' => $producto['dlds_id'],
                    'astro_product_id' => $producto['astro_id'],
                    'activo' => 1
               ]);
               
               //if($res) $this->sync_costs_by_sku($sku);
               
           }else{
    		    	    
        	    if($producto['dlds_id'] == null){
        	        
        	        echo "<br>El producto con SKU ".$sku." no existe en DLDS<br>";
        	        
        	        if(!is_numeric($sku)){
        	            
        	            echo "<br>Si el sku contiene caracteres especiales, actualmente no es posible sincronizarlo debido a limitaciones de prestashop. <br>Si ese es el caso, ingrese el ID del producto en DLDS.<br>";
        	           
        	            
        	        }
        	    }
        	    
                if($producto['astro_id'] == null){
        	        
        	        echo  "<br>El producto con SKU ".$sku." no existe en Astro<br>";
        	     
        	    }
        	}
    		    	
           
       }
                
	    
	}
	
	public function add_product_tienda($sku){
	   
	    
	   $producto = [];
	       
       $producto['sku'] = $sku;
       $producto['dlds_id'] = $this->getThirdPartyIdByReference($sku);
       $producto['astro_id'] = $this->getIdByReferenceOrCheckIfCombination($sku);
       
       $producto_fulfillment = DB::table('productos_tienda')
                                        ->where('sku', $sku)
                                        ->limit(1)
                                        ->get();
                                        
       if(count($producto_fulfillment)> 0 ){
           
          echo "<br>El producto con sku ".$sku." ya esta en tienda. <br>";
          DB::table('productos_fulfillment')->where('sku', strval($sku))->delete();
          
       }else{
           
           echo "<br>Producto con SKU: ".$sku." No esta en tienda. Agregando...<br>";
           
           if($producto['dlds_id'] && $producto['astro_id']){
               
              $res =  DB::table('productos_tienda')->insert([
                    'sku' => $sku,
                    'costo' => 0,
                    'dlds_product_id' => $producto['dlds_id'],
                    'astro_product_id' => $producto['astro_id'],
                    'activo' => 1
               ]);
               
               
               DB::table('productos_fulfillment')->where('sku', strval($sku))->delete();
               
           }else{
    		    	    
        	    if($producto['dlds_id'] == null){
        	        
        	        echo "<br>El producto con SKU ".$sku." no existe en DLDS<br>";
        	        
        	        if(!is_numeric($sku)){
        	            
        	            echo "<br>Si el sku contiene caracteres especiales, actualmente no es posible sincronizarlo debido a limitaciones de prestashop. <br>Si ese es el caso, ingrese el ID del producto en DLDS.<br>";
        	           
        	            
        	        }
        	    }
        	    
                if($producto['astro_id'] == null){
        	        
        	        echo  "<br>El producto con SKU ".$sku." no existe en Astro<br>";
        	     
        	    }
        	}
    		    	
           
       }
                
	    
	}
	
	public function getThirdPartyIdByReference($reference){
	    
	    $product = \DB::connection('dlds')->table('r3pa_product')->select('id_product')->where('reference', $reference)->first();
		
// 		$web_service_dlds = new PrestaShopWebservice(
//             $this->THIRD_PARTY_BASE_URL,
//             $this->THIRD_PARTY_API_KEY,
//             false
//         );
        
        try{
            
            // $producto =  $web_service_dlds->get([
            //         "url" =>
            //         $this->THIRD_PARTY_BASE_URL."/api/products&filter[reference]=".$reference,
            // ]);
            
            if($product){
    		    
    		    return $product->id_product;
    		    
    		}else{
    		    
    		    return null;
    		}
            
        }catch(Throable $ex){
            
            echo "Error";
            die;
        }
		
		
	}
	
	public function getIdByReferenceOrCheckIfCombination($reference, $check = false){
	    
	    if($check){
	        
            $product_combination = DB::table('asTr_product_attribute')
                     ->where('reference', $reference)
                     ->limit(1)
                     ->get();
 		                    
 		  		
     		if(count($product_combination) > 0 && $product_combination[0]->id_product_attribute){
     		    
    			$product_id = $product_combination[0]->id_product_attribute;
    			
    			return $product_id;
    
    		}
    		
    		return null;
	        
	    }
	    
	    
 		
 		$product = DB::table('asTr_product')
 		                    ->where('reference', $reference)
 		                    ->limit(1)
 		                    ->get();
 		
 		
 		if(count($product) > 0 && $product[0]->id_product){
 		    
			$product_id = $product[0]->id_product;
			
			return $product_id;

		}
		
        $product_combination = DB::table('asTr_product_attribute')
 		                    ->where('reference', $reference)
 		                    ->limit(1)
 		                    ->get();
 		                    
 		  		
 		if(count($product_combination) > 0 && $product_combination[0]->id_product_attribute){
 		    
			$product_id = $product_combination[0]->id_product_attribute;
			
			return $product_id;

		}
 	    
	    
	    return null;
	    
	
	}
	
	public function check_products_tienda(){
	    
	   $products_to_sync = DB::table('productos_tienda')->select('*')
                                                          ->orderBy('id', 'desc')
                                                          ->get();
        
        if(count($products_to_sync) <= 0) die('Nada para sincronizar');
        
        foreach( $products_to_sync as $product){
            
        	$productosFulfillment[$product->sku] = array(
				'astro_id' => $product->astro_product_id,
				'dlds_id' => $product->dlds_product_id
			);
        }
        
        // loop the ids and get the stock for each fulfillment product
		foreach ($productosFulfillment as $sku => $product) {
	
			try {
			    
			    $thirdPartyStock = $this->get_stock_sku($sku);
			    
			    if($thirdPartyStock > 0){
			        
			        $this->add_product($sku);
			        echo "<br>SKU: ".$sku." eliminado de tienda<br>";;
			        
			        DB::table('productos_tienda')->where('sku', $sku)->delete();
			        
			        
			    }
            
                
            }catch(Throwable $ex){
                
                error_log("Error");
                error_log($ex->getMessage());
            }
			
		}
	    
	    
	}
	
	public function get_stock_sku($sku){
	    
	    $product = DB::table('productos_fulfillment_stock')->select('*')
                                                          ->where('sku', $sku)
                                                          ->first();
        if(!$product){
            
            return -1;
        }                                                
                                                         
                                                          
        $qty = $product->quantity;
                                                          
        $qty = $this->validate_stock($sku, $qty);
        
        if(in_array($sku, $this->special_sku)){
            
            $qty = $qty - 0;
        	
        }else{
            
        	$qty = $qty - 3;
        }
    
        
        if($qty < 0) $qty = 0;
        
        
        return $qty;
	}
	
	
	public function get_stock_defontana_notification(Request $request){
	    
	    
	    try{
	        
    	    $stock = $request->all();
    	    
    	   // var_dump($stock['response']);
    	    
    	   // die;
    	   
    	    
    	    foreach($stock['response'] as $stock_notification){
    	        
    	       // var_dump($stock_notification);
    	       // die;
    	        
                $products_to_sync = DB::table('productos_fulfillment_stock')->select('*')
                                                          ->where('sku', $stock_notification['code'])
                                                          ->get();
                                                          
                
                $stock_bsale = 0;
                
                if(count($products_to_sync) <= 0){
                    
                    $res =  DB::table('productos_fulfillment_stock')->insert([
                        'sku' => $stock_notification['code'],
                        'quantity' => $stock_notification['stock']
                   ]);
                    
                    $sn = DB::table('productos_fulfillment_stock')->where('sku', $stock_notification['code'])->first();
                    
                    if($sn->quantity>$stock_notification['stock']){
                        
                         $stock_bsale = $sn->quantity - $stock_notification['stock'];
                         
                    }else if($sn->quantity<$stock_notification['stock']){
                        
                        $stock_bsale = $stock_notification['stock'] - $sn->quantity;
                        
                    }else{
                        
                        $stock_bsale = 0;
                        
                    }

                }else{
                    
                    
                    $sn = DB::table('productos_fulfillment_stock')->where('sku', $stock_notification['code'])->first();
                    
                    if($sn->quantity>$stock_notification['stock']){
                        
                         $stock_bsale = $sn->quantity - $stock_notification['stock'];
                         
                    }else if($sn->quantity<$stock_notification['stock']){
                        
                        $stock_bsale = $stock_notification['stock'] - $sn->quantity;
                        
                    }else if($sn->quantity>$stock_notification['stock']){
                        
                        $stock_bsale = $stock_notification['stock'] - $sn->quantity;
                    
                    }else{
                        
                        $stock_bsale = 0;
                        
                    }
                    
                    DB::table('productos_fulfillment_stock')->where('sku', $stock_notification['code'])->update(['quantity' => $stock_notification['stock']]);
                    
                    // try{
                        
                    //     $dlds_product = \DB::connection('dlds')->table('r3pa_product')->select('id_product')->where('reference', $stock_notification['code'])->first();
                    
                    //     if($dlds_product){
                            
                    //         \DB::connection('dlds')->table('r3pa_stock_available')->where('id_product', $dlds_product->id_product)->update(['quantity' => $stock_notification['stock']]);
                            
                    //     }
      
                    // }catch(Throwable $ex){
                        
                        
                    // }

                }
                
                
                try{
                    
                    if($stock_bsale){
                        
                        if($stock_bsale > 0){
                            
                            $this->incrementBsaleStock($stock_notification['code'], $stock_bsale);
                            
                        }else{
                            
                            $stock_bsale = $stock_bsale*-1;
                            
                            $this->decrementBsaleStock($stock_notification['code'], $stock_bsale);
                            
                        }
                        
                        
                        
                    }
                    
                }catch(Exception $ex){
                    
                }
                
    	        
    	    }
    	    
    	    $stock = json_encode($stock['response']);
	     
            $res =  DB::table('productos_fulfillment_stock_notification')->insert([
                    'value' => $stock,
                    'status' => 1
            ]);
	        
	        
	    }catch(Throwable $ex){
	        
    	    $res =  DB::table('productos_fulfillment_stock_notification')->insert([
                    'value' => $ex->getMessage(),
                    'status' => 0
            ]);
                    
        }
                
	    

	    
	    return true;
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

	protected function incrementBsaleStock($bsaleProductSKU, $stockToAdd, $productPrice = 0) {
	    
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

	protected function decrementBsaleStock($bsaleProductSKU, $stockToDecrease, $productPrice) {

        echo "<br>Decrementando stock bsale";
		//todo: temporal fix: sustituimos símbolo de más para poder usar como query param
		$bsaleProductSKU = str_replace('+', '%2B', $bsaleProductSKU);

		$bsaleProductID = $this->getBsaleProductID($bsaleProductSKU);

		//officeId 6 es Bodega Fulfillment
		$bodyConsumption = '{
			"note": "Equiparación de stock vía API Fulfillment",
			"officeId": ' . $this->bsaleBodegaFulfillmentId . ',
			"details": [
				{
				"quantity": ' . $stockToDecrease . ',
				"variantId": ' . $bsaleProductID . ',
				"cost": ' . $productPrice . '
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

		if ($code != '201') {
			$subject = 'Error en API de bsale (decrementBsaleStock) para SKU ' . $bsaleProductSKU . '<br>';
			$body = 'Código del error: ' . $code . '<br>Respuesta de la API: <br>' . $response;

// 			$sqlSyncInsertLog = "(0, 0, '$bsaleProductSKU', 0, 0, 0, 0, 'ERR $subject | $body', $batchCode)";
// 			$this->createSyncLog($sqlSyncInsertLog);

			//$this->sendEmail($body, $subject);
		}
	}
	
	protected function getDLDSProductPrice($sku){
	    
	    $date = Date('Y-m-d');
        $date .= " 00:00:00";
        $priceReduction = 0;
	    
	    $product = DB::table('productos_fulfillment')->select('*')
                                                      ->where('sku', $sku)
                                                      ->first(); 
                                                      
        if($product){
            
            $prodID = (int)$product->dlds_product_id;
            
            $product_dlds = \DB::connection('dlds')->table('r3pa_product')->select('*')->where('id_product', $prodID)->first();
            
            $product_base_price = $product_dlds->price;
            
            echo "Precio base para ".$sku." en DLDS: ".$product_base_price;
            
            $specific_price = \DB::connection('dlds')->table('r3pa_specific_price')->select('*')->where('id_product', $prodID)->where('to', ">=", $date)->where('from', "<=", $date)->orderBy('to', 'desc')->orderBy('reduction', 'desc')->first();
			    
		    
		    if($specific_price){
		        
		        $priceReduction = (float)$specific_price->reduction;

		    }else{
		   
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
		    
		    
			if($priceReduction != null){
				$totalProductPrice = round(($product_base_price - ($product_base_price * $priceReduction)), 0, PHP_ROUND_HALF_UP);
			}else{
				$totalProductPrice = $product_base_price;
			}
		    
		    
		    echo "<br>Precio con descuento: ".$totalProductPrice."<br>";
		    
		    return $totalProductPrice;
        }
	
	}
	
	
	public function get_bsale_stock($bsaleProductSKU){
	    
	    
	    $bsaleProductSKU = str_replace('+', '%2B', $bsaleProductSKU);

		$bsaleProductID = $this->getBsaleProductID($bsaleProductSKU);
		
		echo "Bsale producto ID: ".$bsaleProductID."<br><br>";
		
		$session = curl_init($this->bsaleUrlApi .'stocks/'.$bsaleProductID.'.json?officeid=6');

		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->bsaleApiHeaders);
		curl_setopt($session, CURLOPT_HTTPGET, true);
		curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);
		
		$response = curl_exec($session);
		$code = curl_getinfo($session, CURLINFO_HTTP_CODE);
		curl_close($session);
		
		var_dump($response);
	    
	}
	
	public function sync_costs(){
	    
	   \Log::info("Costs Cron is Running ... !");
	   
	 
        	   
    	   set_time_limit(0);
    	   $date = Date('Y-m-d');
           $date .= " 00:00:00";
    	   $productos_fulfillment = DB::table('productos_fulfillment')
    	                                   ->where('costo', 0)
    	                                   ->where('dlds_product_id', "!=", 0)
    	                                   ->orderBy('id', 'desc')
    	                                   ->get();
    	    
            foreach ($productos_fulfillment as $productFulfill){
                
                try{
        			$prodID = (int)$productFulfill->dlds_product_id;
        			$priceReduction = null;
        			$totalProductPrice = 0;
        			
        	
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
        
        		
        			
        			try{
                        
                        DB::table('productos_fulfillment')->where('sku', $productFulfill->sku)->update(['costo' => $totalProductPrice, 'to_sync' => 0]);
                        
                        echo "Producto: ".$productFulfill->sku;
                        echo "<br>Costo: ".$totalProductPrice."<br><br>";
                        
                    }catch(Exception $ex){
                        var_dump($productFulfill);
                        echo "<br>Error: ".$ex->getMessage()."<br><br>";
                    }
                    
                    		
            	}catch(Exception $ex){
                        echo "<br>Error".$productFulfill->sku.": ".$ex->getMessage()."<br><br>";
                        
                }
        	}

	}
	
	
	public function sync_stock_delight(){
        
        //Revisamos tabla de order fulfillment para ver si hay orders en cola, si hay, nos traemos los ids de órdenes únicos
        $products_to_sync = \DB::connection('delight')->table('productos_fulfillment')->select('*')
                                                              ->orderBy('id', 'desc')
                                                              ->get();
                                                              
                                                              
        
        if(count($products_to_sync) <= 0) die('Nada para sincronizar');
        
        foreach( $products_to_sync as $product){
            
        	$productosFulfillment[$product->sku] = array(
				'delight_id' => $product->astro_product_id,
				'dlds_id' => $product->dlds_product_id
			);
        }
        
        // loop the ids and get the stock for each fulfillment product
		foreach ($productosFulfillment as $sku => $product) {
		  
			$thirdPartyStock = 0;
			$nuevoStock = 0;
			
			try {
			    
			    $thirdPartyStock = $this->get_stock_sku($sku);
			    
			    if($thirdPartyStock == -1){
			        //No existe aun en la nueva tabla
			        
			        $product_dlds = \DB::connection('dlds')->table('r3pa_stock_available')->select('*')->where('id_product', $product['dlds_id'])->first();
			        
			        
			        
			        if($product_dlds){
			           
			            
			            $thirdPartyStock = $this->validate_stock($sku, $product_dlds->quantity);
			            
			        }else{
			            
			            $thirdPartyStock = 0;
			        }
			        
			    } 
			    
			    echo "Stock para ".$sku.": ".$thirdPartyStock."<br><br>";
			    
			    
			    if($thirdPartyStock){
			       
                    $productoStockAvailable =  \DB::connection('delight')->table('psjk_stock_available')->select('id_stock_available', 'quantity')->where('id_product', $product['delight_id'])->first();
    				
    				if($productoStockAvailable){
    				    
    				    $astroStock = $productoStockAvailable->quantity;

        				//$this->update_stock_delight($astroStock, $thirdPartyStock, false, $product['delight_id'], $sku);
    				    
    				}else{
    				    
    				    //$this->update_stock_delight(0, $thirdPartyStock, false, $product['delight_id'], $sku);
    				    
    				}
        				
			        
			    }
			    
                
            }catch(Throwable $ex){
                
                error_log("Error");
                error_log($ex->getMessage());
            }
			
		}
                                                    
    }
}
