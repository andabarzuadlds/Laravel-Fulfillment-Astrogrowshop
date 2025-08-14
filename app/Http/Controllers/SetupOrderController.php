<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Services\PrestashopService;
use Illuminate\Support\Facades\Log;


class SetupOrderController extends Controller 
{
    public function setupFulfillmentOrder(Request $request){
        ignore_user_abort(true);
        
        //Chequeamos el token con el que viene el request para validarlo y elegir el Servicio correspondiente (token-based service)
        $requestInputs = $request->all();
        Log::warning('entró el request');
        //Si no existe el x-token no puede seguir
        // if(!array_key_exists('x-token', $requestInputs)){
        //     die('No está validado');
        // }

        //Instanciamos el servicio de Prestashop basado en el x-token que se recibió
        $apiService = PrestashopService::getApiServiceBasedOnToken($requestInputs['x-token']);
        if(is_null($apiService)){

            //TODO: return http status code
            die('No está validado');
        }

            
        $ordersIdToFulfill = DB::select('SELECT DISTINCT(id_order) 
                                                FROM orders_fulfillment 
                                                WHERE (siendo_procesada = false || siendo_procesada is null)');


        if(count($ordersIdToFulfill) <= 0) die('Nada para sincronizar');

        //Loopeamos para sacar un array simple de ids, en vez del arreglo de stdClass que retorna el DB::select()
        foreach($ordersIdToFulfill as $orderToFulfill){
            
            if($orderToFulfill->id_order){
                $ordersIdsToFulfillArray[] = $orderToFulfill->id_order;
                
                //error_log($orderToFulfill->id_order);
            }
            
        }
        

        
        //Tenemos que setear estas ordenes como que están siendo "procesadas" para que no vayamos a reprocesar órdenes o perder órdenes que no han sido procesadas pero también viven en la tabla
        // DB::table('orders_fulfillment')
        //         ->whereIn('id_order', $ordersIdsToFulfillArray)
        //         ->update(['siendo_procesada' => true]);

        //Agrupamos por producto porque tenemos que enviarle productos únicos a la api de prestashop,
		//  entonces sumamos la cantidad de productos que hay si un producto está repetido. 
		
		    
            $productsToFulfill = DB::select('SELECT o.sku_producto, p.dlds_product_id as id_producto_dlds, 
                                SUM(o.qty_producto) as qty_producto_total
                                FROM orders_fulfillment o
                                LEFT JOIN productos_fulfillment p ON o.sku_producto = p.sku
                                WHERE o.id_order IN(' . implode(',', $ordersIdsToFulfillArray) . ') 
                                GROUP BY o.sku_producto, p.dlds_product_id');
		

        //Hay órdenes para procesar, enviamos los productos de estas órdenes a la api
        $apiService->postPrestashopOrder($productsToFulfill, $ordersIdsToFulfillArray);
    }
    
    public function setupFulfillmentOrderV2(Request $request){
        ignore_user_abort(true);
        
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
        $ordersIdToFulfill = DB::select('SELECT DISTINCT(id_order) 
                                                    FROM orders_fulfillment 
                                                    WHERE (siendo_procesada = false || siendo_procesada is null)');
                                                    


        if(count($ordersIdToFulfill) <= 0) die('Nada para sincronizar');

        //Loopeamos para sacar un array simple de ids, en vez del arreglo de stdClass que retorna el DB::select()
        foreach($ordersIdToFulfill as $orderToFulfill){
            
            $ordersIdsToFulfillArray[] = $orderToFulfill->id_order;
            
            DB::table('orders_fulfillment')
                ->whereIn('id_order', $ordersIdsToFulfillArray)
                ->update(['siendo_procesada' => true]);
                
            try{
                
                $productsToFulfill = DB::select('SELECT o.sku_producto, p.dlds_product_id as id_producto_dlds, 
                                                SUM(o.qty_producto) as qty_producto_total
                                                FROM orders_fulfillment o
                                                LEFT JOIN productos_fulfillment p ON o.sku_producto = p.sku
                                                WHERE o.id_order = ' . $orderToFulfill->id_order.' 
                                                GROUP BY o.sku_producto, p.dlds_product_id');
                                                
                error_log(json_encode($productsToFulfill));
                                                
                $apiService->postPrestashopOrder($productsToFulfill, $ordersIdsToFulfillArray);
                
                
                
            }catch(Throwable $e){
                
                error_log($e->getMessage());
                
            }
  
            $ordersIdsToFulfillArray[] = null;
        }
        
    }
    
     public function setupFulfillmentOrderById(Request $request){
        ignore_user_abort(true);
        
        //Chequeamos el token con el que viene el request para validarlo y elegir el Servicio correspondiente (token-based service)
        $requestInputs = $request->all();
        Log::warning('entró el request de pedido por ID');
        //Si no existe el x-token no puede seguir
        if(!array_key_exists('x-token', $requestInputs)){
            die('No está validado');
        }
        
        if(!array_key_exists('id_order', $requestInputs)){
            die('Falta el id del pedido');
        }
        

        //Instanciamos el servicio de Prestashop basado en el x-token que se recibió
        $apiService = PrestashopService::getApiServiceBasedOnToken($requestInputs['x-token']);
        if(is_null($apiService)){

            //TODO: return http status code
            die('No está validado');
        }

        //Revisamos tabla de order fulfillment para ver si hay orders en cola, si hay, nos traemos los ids de órdenes únicos
        // $ordersIdToFulfill = DB::select('SELECT DISTINCT(id_order) 
        //                                             FROM orders_fulfillment 
        //                                             WHERE (siendo_procesada = false || siendo_procesada is null)');
                                                    
        $ordersIdToFulfill = $requestInputs['id_order'];
    
        if(!$ordersIdToFulfill) die('Nada para sincronizar');

        $ordersIdsToFulfillArray[] = $ordersIdToFulfill;
        //Loopeamos para sacar un array simple de ids, en vez del arreglo de stdClass que retorna el DB::select()
        // foreach($ordersIdToFulfill as $orderToFulfill){
        //     $ordersIdsToFulfillArray[] = $orderToFulfill->id_order;
        // }
        
        //Tenemos que setear estas ordenes como que están siendo "procesadas" para que no vayamos a reprocesar órdenes o perder órdenes que no han sido procesadas pero también viven en la tabla
        DB::table('orders_fulfillment')
                ->whereIn('id_order', $ordersIdsToFulfillArray)
                ->update(['siendo_procesada' => true]);

        //Agrupamos por producto porque tenemos que enviarle productos únicos a la api de prestashop,
		//  entonces sumamos la cantidad de productos que hay si un producto está repetido. 
        // $productsToFulfill = DB::select('SELECT o.sku_producto, p.dlds_product_id as id_producto_dlds, 
        //                                             SUM(o.qty_producto) as qty_producto_total
        //                                             FROM orders_fulfillment o
        //                                             LEFT JOIN productos_fulfillment p ON o.sku_producto = p.sku
        //                                             WHERE o.id_order IN(' . implode(',', $ordersIdsToFulfillArray) . ') 
        //                                             GROUP BY o.sku_producto, p.dlds_product_id');
        
        $productsToFulfill = DB::select('SELECT o.sku_producto, p.dlds_product_id as id_producto_dlds, 
                                SUM(o.qty_producto) as qty_producto_total
                                FROM orders_fulfillment o
                                LEFT JOIN productos_fulfillment p ON o.sku_producto = p.sku
                                LEFT JOIN asTr_stock_available asa ON asa.id_product = p.astro_product_id
                                WHERE o.id_order = ' . $ordersIdToFulfill.' and o.sku_producto != "SMSSKLKSFEMX5P2"
                                GROUP BY o.sku_producto, p.dlds_product_id');
                 
        if($productsToFulfill && count($productsToFulfill)>0){
            
             //Hay órdenes para procesar, enviamos los productos de estas órdenes a la api
            $apiService->postPrestashopOrder($productsToFulfill, $ordersIdsToFulfillArray);
        }else{
            
            echo "No hay productos para procesar<br><br>";
            
            echo 'SELECT o.sku_producto, p.dlds_product_id as id_producto_dlds, 
                                SUM(o.qty_producto) as qty_producto_total
                                FROM orders_fulfillment o
                                LEFT JOIN productos_fulfillment p ON o.sku_producto = p.sku
                                LEFT JOIN asTr_stock_available asa ON asa.id_product = p.astro_product_id
                                WHERE o.id_order = ' . $ordersIdToFulfill.' and o.sku_producto != "SMSSKLKSFEMX5P2"
                                GROUP BY o.sku_producto, p.dlds_product_id';
        }               

       
    }
    
    public function validateStock($id){
        
        $apiService = PrestashopService::getApiServiceBasedOnToken('UJWRIG3251NR2JNNH69589FABXGRPROD');
        
        $apiService->validateStockID($id);
    }
    
    
    public function validate_stock_defontana($sku){
        
         $apiService = PrestashopService::getApiServiceBasedOnToken('UJWRIG3251NR2JNNH69589FABXGRPROD');
         
         $apiService->validate_stock_defontana($sku);
        
    }
    
    public function getBsaleDocuments(){
        $apiService = PrestashopService::getApiServiceBasedOnToken('UJWRIG3251NR2JNNH69589FABXGRPROD');
         
        $apiService->getbsaleDocuments();
        
    }
}