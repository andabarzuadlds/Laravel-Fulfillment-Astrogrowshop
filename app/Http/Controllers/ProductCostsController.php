<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Services\PrestashopService;
use Illuminate\Support\Facades\Log;
use PrestaShopWebservice;

class ProductCostsController extends Controller
{
    private $THIRD_PARTY_BASE_URL = 'https://administracion.dlds.cl';
	private $THIRD_PARTY_API_KEY = 'UJWRIG3251NR2JNNH69589FABXGRPROD';
	private $ASTRO_DB_USER = 'astrogro_user002';
	private $ASTRO_API_KEY = 'XGKEJB8M26VDWWIVUZHJRCA45GWAECYK';
	private $ASTRO_BASE_URL = 'https://astrogrowshop.cl';
	private $ASTRO_DB_PW = '3JRXTd;eSCi]3JRXTd;eSCi]';
	private $ASTRO_DB_IP = '170.249.236.130';
	private $ASTRO_DB_NAME = 'astrogro_astr0032';
	
	private $id_group = 26;
	
	public function sync_costs(){
	    
	   \Log::info("Costs Cron is Running ... !");
	   
	   set_time_limit(0);
	    
	   $productos_fulfillment = DB::table('productos_fulfillment')
	                                   	->where('cost', 0)
	                                    ->get();
	    
        foreach ($productos_fulfillment as $productFulfill){
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
                
                DB::table('productos_fulfillment')->where('sku', $productFulfill->sku_producto)->update(['costo' => $totalProductPrice]);
                
            }catch(Exception $ex){
                
            }
		}
	}
	
	public function add_products_to_sync_cole(){
	    
	    DB::table('productos_fulfillment')->update(['to_sync' => 1]);
	    
	    \Log::info("Costs Cole Cron is Running ... !");
	}
	
	public function sync_costs_by_sku($sku){
	    
	   \Log::info("Costs by sku Cron is Running ... !");
	   
	   set_time_limit(0);
	    
	   $productos_fulfillment = DB::table('productos_fulfillment')
	                                    ->where('sku', $sku)
	                                    ->get();
	    
       $web_service_dlds = new PrestaShopWebservice(
            $this->THIRD_PARTY_BASE_URL,
            $this->THIRD_PARTY_API_KEY,
            false
        );
	    
	    foreach($productos_fulfillment as $producto){
			
			try{
			    
			    echo "Producto: ".$producto->sku;
			    
			    $productXML = $web_service_dlds->get([
                    "url" =>
                    $this->THIRD_PARTY_BASE_URL."/api/products/".$producto->dlds_product_id,
                ]);
                
                $specificPriceRules = $web_service_dlds->get([
                    "url" =>
                    $this->THIRD_PARTY_BASE_URL."/api/specific_prices&filter[id_product]=".$producto->dlds_product_id,
                ]);
                
   
                $priceReduction = 0;
                $date = Date('Y-m-d');
    			$date .= " 00:00:00";
    			
    			$specific_price = \DB::connection('dlds')->table('r3pa_specific_price')->select('*')->where('id_product', $producto->dlds_product_id)->where('to', ">=", $date)->where('from', "<=", $date)->orderBy('to', 'desc')->orderBy('reduction', 'desc')->first();
                
    			
    			foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
    			    
        			        
        			    $specialPriceData = $web_service_dlds->get([
                            "url" =>
                            $this->THIRD_PARTY_BASE_URL."/api/specific_prices/".$specificPriceIds['id'],
                        ]);
        				
        		// 		we know that Group 26 is the Franquicias group in DLDS, which is the one that Astro belongs to
        				if((int)$specialPriceData->specific_price->id_group === $this->id_group && $date >= $specialPriceData->specific_price->from && $date <= $specialPriceData->specific_price->to){
        
        		            // get the reduction assigned to this Group
        		            if((float)$specialPriceData->specific_price->reduction > $priceReduction){
        		                
        		                $priceReduction = (float)$specialPriceData->specific_price->reduction;
        		                //echo "Price reduction 1: ".$priceReduction."<br>";
        		            }
        					
        				}
        				
        			    if((int)$specialPriceData->specific_price->id_group === $this->id_group && $specialPriceData->specific_price->to == "0000-00-00 00:00:00"){
        
        		            //get the reduction assigned to this Group
        		
        		            if((float)$specialPriceData->specific_price->reduction > $priceReduction){
        		                
        		                $priceReduction = (float)$specialPriceData->specific_price->reduction;
        		                echo "Price reduction 1: ".$priceReduction."<br>";
        		            }
        					
    
        				}
    			
		    

    			}
			    
			    			
    			$specificPriceRules = $web_service_dlds->get([
                    "url" =>
                    $this->THIRD_PARTY_BASE_URL."/api/specific_prices&filter[id_product]=" . $producto->dlds_product_id."&&filter[id_group]=0?sort=[reduction_desc,to_desc]",
                ]);
                
                		    $priceReduction2 = 0;
    		    $id_reduction = 0;
    		    
    		    			    
    		    
    		    foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
    		        
    			    $specialPriceData = $web_service_dlds->get([
                        "url" =>
                        $this->THIRD_PARTY_BASE_URL."/api/specific_prices/".$specificPriceIds['id'],
                    ]);
                    
                    // echo "<br>DATE: ".$date." - FROM: ".$specialPriceData->specific_price->from." - TO: ".$specialPriceData->specific_price->to."<br><br>";
                    
                    
                    
    			    
    			    if($date >= $specialPriceData->specific_price->from && $date <= $specialPriceData->specific_price->to){
                        
                        // echo "Price reduction old: ".$priceReduction." - vs reduction: ".$specialPriceData->specific_price->reduction."<br>";

    			        if($specialPriceData->specific_price->reduction > $priceReduction || $priceReduction == 0){
    			           
    			             $priceReduction2 = (float)$specialPriceData->specific_price->reduction;
    			             
    			             echo "<br>Primera condicion: ".$priceReduction2;
    			        }
    			       
    				        
    				 }else{
    				        
    				    if($specialPriceData->specific_price->to == "0000-00-00 00:00:00"){
    
    				// 			get the reduction assigned to this Group
    				
    				            if((float)$specialPriceData->specific_price->reduction > $priceReduction){
    				                
    				                $priceReduction2 = (float)$specialPriceData->specific_price->reduction;
    				                echo "<br>Segunda condicion: ".$priceReduction."<br>";
    				            }
    						
    					}
    			        
    			        
    			    }
    			  
    				
    			}
    	    
    	    
    			if( $priceReduction2 && $priceReduction2 > $priceReduction){
    			     
    			     echo "Level 2<br>";
    			     
    			     $priceReduction = $priceReduction2;
    			 }  
    	
    		
    		    if(!$priceReduction){
    	    
    							
        		    $specificPriceRules = $web_service_dlds->get([
                        "url" =>
                        $this->THIRD_PARTY_BASE_URL.'/api/specific_prices&filter[id_product]=' . $producto->dlds_product_id.'&&filter[to]=[0000-00-00]?sort=[reduction_desc]',
                    ]);
                    
        		    $priceReduction3 = 0;
        		    $id_reduction_3 = 0;
        		    
        		    foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
        			    $specialPriceData = $web_service_dlds->get([
                            "url" =>
                            $this->THIRD_PARTY_BASE_URL."/api/specific_prices/".$specificPriceIds['id'],
                        ]);
    			    
        			     $priceReduction3 = (float)$specialPriceData->specific_price->reduction;
        			     $id_reduction_3 = $specialPriceData->specific_price->reduction->id;
        			     
        			     echo "ID REDUCTION: ".$id_reduction_3;
        			     break;
        			}
        			    
        			    
        			 if($priceReduction3 > $priceReduction){
        			     
        			     echo "Level 3 (".$priceReduction.")<br> ";
        			     
        			     $priceReduction = $priceReduction3;
        			 } 					    
        		}
    					
    					
    			echo "<br>Price reduction: ".$priceReduction."<br><br>";
    			
    
    			$price = (float)$productXML->product->price;
    
    			//si hay valor en reducción, se lo resto al precio, sino, el precio es el que retornó la api
    			$totalProductPrice = (float)($priceReduction ? 
    									round(($price - ($price * $priceReduction)), 0, PHP_ROUND_HALF_UP) : $price);
    
    			//insertar en columna de "costo" de la tabla de productos_fulfillment
    		 	//$res = DB::table('productos_fulfillment')->where('sku', $sku)->update(['costo' => $totalProductPrice]);
    		 	
    		 	$res = DB::table('productos_fulfillment')->where('sku', $sku)->update(['costo' => $totalProductPrice]);
    		 	\Log::info("Product price:");
    		 		\Log::info($totalProductPrice);
    		 	
    			//echo 'UPDATE productos_fulfillment SET costo = ' . $totalProductPrice . ' WHERE sku = "' . $producto->sku . '";<br>';
    			//echo '<tr><td>'.$row['sku'].'</td><td>'.$totalProductPrice.' - Reduction: '.$priceReduction.'</td></tr>';
                echo "Total product price: ".($price - ($price*$priceReduction));
                
    //             if($res){
    			    
    // 			    echo $totalProductPrice;
    			    
    // 			}else{
    			    
    // 			    echo "RES: ".$res;
    // 			}

			    
			}catch(Exception $ex){
			    
			    echo "Error: ".$ex->getMessage();
			}
	        
	       // DB::table('productos_fulfillment')
	       //     ->where('id', $producto->id)
        //         ->update(['to_sync' => 0]);
	        
	    }
	    
	    
	}
	
	
	public function test($sku){
	    
	   $date = Date('Y-m-d');
       $date .= " 00:00:00";
    
            
        $web_service_dlds = new PrestaShopWebservice(
            $this->THIRD_PARTY_BASE_URL,
            $this->THIRD_PARTY_API_KEY,
            false
        );
	       
	   $productsToFulfill = DB::table('productos_fulfillment')
	                                    ->where('sku', $sku)
	                                    ->get();
	    
	    foreach ($productsToFulfill as $productFulfill){
			$prodID = (int)$productFulfill->dlds_product_id;
			$prodQty = 1;
			$priceReduction = null;
			$totalProductPrice = 0;
			
			
			//get the price of each article, multiply it by product quantity and then add that to the total, so we get the overall total at the end
			$priceXML = $web_service_dlds->get(['resource' => 'products/' . $prodID]);
			
			$specific_price = \DB::connection('dlds')->table('r3pa_specific_price')->select('*')->where('id_product', $prodID)->where('to', ">=", $date)->where('from', "<=", $date)->orderBy('to', 'desc')->orderBy('reduction', 'desc')->first();
			
			    
		    $priceReduction = (float)$specific_price->reduction;
		    

			//we need to get the specific_prices resource for this product 
			//because Astro has special prices from dlds
			//we need to check if this product is selected to have a discount
			$specificPriceRules = $web_service_dlds->get(['resource' => 'specific_prices&filter[id_product]=' . $prodID]);

			//we loop the prices rules and check the Group they are assigned to
			foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
				$specialPriceData = $web_service_dlds->get(['resource' => 'specific_prices/' . $specificPriceIds['id']]);
				
				// we know that Group 5 is the Franquicias group in DLDS, which is the one that Astro belongs to
				if((int)$specialPriceData->specific_price->id_group == $this->id_group){
                    
                   
                    if($specialPriceData->specific_price->reduction > $priceReduction && $specialPriceData->specific_price->to == "0000-00-00 00:00:00"){
                         echo "GROUP REDUCTIONN ".$specialPriceData->specific_price->reduction."<br>";
                        //get the reduction assigned to this Group
				       $priceReduction = (float)$specialPriceData->specific_price->reduction;
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
			
			echo "Price: ".$totalProductPrice." - Reduction: ".$priceReduction;
			echo "<br>Price with iva: ".$priceWithIva;
        
	    }
            

	}
	
	public function masive_add_products(){
	    
	    $products = array(
                        array("val0"=>"MLSLX5CMCCL"),
                        array("val0"=>"SMESHLFEMX3+1"),
                        array("val0"=>"FRCNTF500ML"),
                        array("val0"=>"AYPBLRSOTD16OZ"),
                        array("val0"=>"SMDPMMTFEMX3"),
                        array("val0"=>"PPMGKJRSF"),
                        array("val0"=>"TXOZETPQCY"),
                        array("val0"=>"BGBLRO1307SB"),
                        array("val0"=>"AYPBLRSNA16OZ"),
                        array("val0"=>"SMPSANBAUTOX3+1"),
                        array("val0"=>"VPATQSBLUE"),
                        array("val0"=>"TXOZBNCCPU"),
                        array("val0"=>"MLGAGR38PINK"),
                        array("val0"=>"TXOZBNCCRED"),
                        array("val0"=>"PPAHUMSAZ"),
                        array("val0"=>"BGBLK598AMBER"),
                        array("val0"=>"BGBLK165BLUE"),
                        array("val0"=>"SMBDCRCLAUTOX3"),
                        array("val0"=>"PPMGKJVDF"),
                        array("val0"=>"BGBLK165TEAL"),
                        array("val0"=>"PPAHUMSVE"),
                        array("val0"=>"PPAHSLCHNE"),
                        array("val0"=>"TXBLPINW06"),
                        array("val0"=>"BGBLSH432GR"),
                        array("val0"=>"TXOZETPQ"),
                        array("val0"=>"VPATQSWHITE"),
                        array("val0"=>"TXOZ4EN1CCRED"),
                        array("val0"=>"VPATNVKITWHITE"),
                        array("val0"=>"SMFBPLMNDAUTOX3"),
                        array("val0"=>"AYPBLRSCE12OZ"),
                        array("val0"=>"BGPMGTKMCL"),
                        array("val0"=>"SMPSPRPUKFEMX3+1"),
                        array("val0"=>"PPMGKJKKY"),
                        array("val0"=>"FRWLRZ5LT"),
                        array("val0"=>"SMSNSNTNLAUTOX3"),
                        array("val0"=>"FRANB521LT"),
                        array("val0"=>"WAXPMG10TCHADKTS"),
                        array("val0"=>"PPAHSLCHVE"),
                        array("val0"=>"PPMGKJCL"),
                        array("val0"=>"VPPFHTKFDES"),
                        array("val0"=>"VPDVIQ2BLK"),
                        array("val0"=>"MLSLX6CMGRN"),
                        array("val0"=>"SMSMTLFEMX3"),
                        array("val0"=>"SMBDGOFEMX3"),
                        array("val0"=>"SMRQMAUSAPREAUTOX3"),
                        array("val0"=>"QUPMGKL11"),
                        array("val0"=>"WAXPMGMBWNRS"),
                        array("val0"=>"FRBCPKBT500ML"),
                        array("val0"=>"SMSNBKCAUTOX3"),
                        array("val0"=>"CMGGEXTT100MM"),
                        array("val0"=>"TXRTHDCSBLACK"),
                        array("val0"=>"SMSMPL8AUTOX3"),
                        array("val0"=>"BGBLR3BLACK"),
                        array("val0"=>"AYPOCBORCA7CM"),
                        array("val0"=>"SMRQFEACBDAUTOX3"),
                        array("val0"=>"AYPBLRSNE12OZ"),
                        array("val0"=>"SMSSOKFEMX3"),
                        array("val0"=>"MLGAMR55RED"),
                        array("val0"=>"CPANHS150ML"),
                        array("val0"=>"BGPMGKURDF"),
                        array("val0"=>"BGBLK293WHITE"),
                        array("val0"=>"SMDPCBDCPSAUTOX3"),
                        array("val0"=>"PPPMGKSHRASW"),
                        array("val0"=>"WAXPMGMBWCLF"),
                        array("val0"=>"TXHTCLON"),
                        array("val0"=>"SMSWSTZENFEMX3+1"),
                        array("val0"=>"SMRKCRAUTOX32X1"),
                        array("val0"=>"SMSMGEGLFEMX3"),
                        array("val0"=>"VPPFPROXY"),
                        array("val0"=>"AYPZGZPOP"),
                        array("val0"=>"8436592320639"),
                        array("val0"=>"SMSSBBGAUTOX3"),
                        array("val0"=>"WAXPMG10TCHADSGR"),
                        array("val0"=>"BGCGCH-002"),
                        array("val0"=>"VPDVIQCBLK"),
                        array("val0"=>"SMRQWUSAPREAUTOX3"),
                        array("val0"=>"MLSLX9CMBLK"),
                        array("val0"=>"AYPATTR60ML"),
                        array("val0"=>"BGBLR2YELLOW"),
                        array("val0"=>"SMSWSCSXLAUTOX3+1"),
                        array("val0"=>"FRBCHB1LT"),
                        array("val0"=>"SMSWSBSKRFFEMX3+1"),
                        array("val0"=>"SMMSBCNSDSLFEMX3"),
                        array("val0"=>"VPPFPROXWB"),
                        array("val0"=>"SMSSGRCRFEMX3"),
                        array("val0"=>"SMSSCBDXXLAUTOX3"),
                        array("val0"=>"SMSNSSPSKAUTOX10"),
                        array("val0"=>"VPPFBUDSYGLA"),
                        array("val0"=>"ILGGBEFB400W"),
                        array("val0"=>"ILGGBEFB1000W"),
                        array("val0"=>"CMBVS6762"),
                        array("val0"=>"ESCTGT1ML"),
                        array("val0"=>"CMCEVK125MM"),
                        array("val0"=>"TXCOLBBPCAM"),
                        array("val0"=>"ESCTLC1ML"),
                        array("val0"=>"ESCTSPRYGTO5ML"),
                        array("val0"=>"FRATCL1LT"),
                        array("val0"=>"FRATCL3.75LT"),
                        array("val0"=>"FRATCM3.75LT"),
                        array("val0"=>"FRATPK3.75LT"),
                        array("val0"=>"WAXPMGMBWRJ"),
                        array("val0"=>"TXCOOMUMBL"),
                        array("val0"=>"TXCOLBBPRED"),
                        array("val0"=>"FRGKVTMXPLUS1LT"),
                        array("val0"=>"FRBBBB10LT"),
                        array("val0"=>"AYPRAWBAMMINI"),
                        array("val0"=>"AYPRAWBAMFHGRA"),
                        array("val0"=>"FRTCTVCN4KG"),
                        array("val0"=>"AYPDAHDM16"),
                        array("val0"=>"VPCLVFLNB"),
                        array("val0"=>"MCMWKKMPSPHEC"),
                        array("val0"=>"MCMWKKCAPHBD"),
                        array("val0"=>"FRWLRAVEA5L"),
                        array("val0"=>"RGSMPULLAN5L"),
                        array("val0"=>"SMSMANCOFEMX3"),
                        array("val0"=>"SMRQNLAAUTOX3"),
                        array("val0"=>"SMRKICERFEMX10"),
                        array("val0"=>"SMRKCRAUTOX102X1"),
                        array("val0"=>"SMSMPUPUAUTOX3"),
                        array("val0"=>"SMSMCHPAUTOX3"),
                        array("val0"=>"SMRQSRYHNCBDFEMX3"),
                        array("val0"=>"SMRQBCAUTOX3"),
                        array("val0"=>"SMDPCOPFEMX100"),
                        array("val0"=>"SMSWRTXLAUTOX3+1"),
                        array("val0"=>"TCOTVCH40G"),
                        array("val0"=>"SMRKICERFEMX5"),
                        array("val0"=>"SMFBORBLGAUTOX3"),
                        array("val0"=>"SMFBGPFRTAUTOX3"),
                        array("val0"=>"SMSWSBDXLAUTOX25"),
                        array("val0"=>"SMSWSCCMF1AUTOX100"),
                        array("val0"=>"PPPMGKRGMAR"),
                        array("val0"=>"SMFBC4AUTOX3"),
                        array("val0"=>"VPSZBPTYNEG"),
                        array("val0"=>"BGPMGTKMNG"),
                        array("val0"=>"BGCGCL-103Y"),
                        array("val0"=>"SMRQRCAAUTOX3"),
                        array("val0"=>"SMFBG14AUTOX3"),
                        array("val0"=>"SMFBCMHAUTOX3"),
                        array("val0"=>"MLSLX9CMPRP"),
                        array("val0"=>"SMRVDCAUTOX3"),
                        array("val0"=>"SMFBFTBRYAUTOX1"),
                        array("val0"=>"SMFBSTDGAUTOX3"),
                        array("val0"=>"AYPBLRSMO8OZ"),
                        array("val0"=>"SMSNS49CBDFEMX3"),
                        array("val0"=>"BGBLR3TEAL"),
                        array("val0"=>"FRANCNSB1LT"),
                        array("val0"=>"PPICLQDRAOLIV"),
                        array("val0"=>"SMSWSSADAUTOX3+1"),
                        array("val0"=>"FRBBBH1LT"),
                        array("val0"=>"FRBBAV1LT"),
                        array("val0"=>"VPPFPROXSB"),
                        array("val0"=>"SMSNSSKXLAUTOX100"),
                        array("val0"=>"SMSNSKN1XLFEMX100"),
                        array("val0"=>"SMSNNTLPXLFEMX100"),
                        array("val0"=>"SMSNNTLAUTOX10"),
                        array("val0"=>"SMSNHKAUTOX10"),
                        array("val0"=>"SMSNESKAUTOX10"),
                        array("val0"=>"FRBCHG1LT"),
                        array("val0"=>"ILGGHPS400"),
                        array("val0"=>"BGBLP37"),
                        array("val0"=>"BGBL80WHITE"),
                        array("val0"=>"TXCOCBUMCAM"),
                        array("val0"=>"TXBLWHTSHSLM"),
                        array("val0"=>"FRBCRST120ML"),
                        array("val0"=>"TXOZSHOBLK"),
                        array("val0"=>"SMSSTBNFEMX3SUP"),
                        array("val0"=>"SMBSFCTFFEMX7"),
                        array("val0"=>"FRBBAM10LT"),
                        array("val0"=>"FRBBTM10LT"),
                        array("val0"=>"FRATPROB4.5KG"),
                        array("val0"=>"FRATPROC4.5KG"),
                        array("val0"=>"FRATST1LT"),
                        array("val0"=>"FRATST3.75LT"),
                        array("val0"=>"FRATBAB3.75LT"),
                        array("val0"=>"FRATCM1LT"),
                        array("val0"=>"FRATPROG4.5KG"),
                        array("val0"=>"VPPFGUAPDOCK"),
                        array("val0"=>"TXCOOMUMBLU"),
                        array("val0"=>"TXCONYBIBLCA"),
                        array("val0"=>"TORAMINCEXFR"),
                        array("val0"=>"HYAGHP8PRODRY55CM"),
                        array("val0"=>"FRCNAF5LT"),
                        array("val0"=>"FRBBFM10LT"),
                        array("val0"=>"FRBBBG10LT"),
                        array("val0"=>"BGPMGTKMMCL"),
                        array("val0"=>"BGCGCP-1110"),
                        array("val0"=>"AYPRAWBAMMINIG"),
                        array("val0"=>"FRTCSL5LT"),
                        array("val0"=>"FRGEOROCGUA200GR"),
                        array("val0"=>"AYPDAHDM7"),
                        array("val0"=>"AYPDAHDM5"),
                        array("val0"=>"MCMWKMPSPHS"),
                        array("val0"=>"MCMWKMPSPHECTT"),
                        array("val0"=>"CPWLOPT5LT"),
                        array("val0"=>"FRWLBIPACK"),
                        array("val0"=>"FRWLBIVE5LT"),
                        array("val0"=>"FRWLFLORO5LT"),
                        array("val0"=>"FRWLSWBL5LT"),
                        array("val0"=>"SMSMAXPPFEMX3"),
                        array("val0"=>"SMRQHBAAUTOX3"),
                        array("val0"=>"SMRKTDAUTOX10"),
                        array("val0"=>"SMRKHIBREGX5"),
                        array("val0"=>"SMRKAZAAUTOX10"),
                        array("val0"=>"SMSSSAMFEMX3"),
                        array("val0"=>"FRANBFX1LT"),
                        array("val0"=>"CMCEEXMT250"),
                        array("val0"=>"VPPFPEPRPDOCK"),
                        array("val0"=>"VPPFBUDSYVOO"),
                        array("val0"=>"VPPFPEPROTPRD"),
                        array("val0"=>"VPPFPEPROTPOG"),
                        array("val0"=>"VPPFPROXBACAP"),
                        array("val0"=>"BGCGESQZ-041"),
                        array("val0"=>"SMRQPFFEMX3"),
                        array("val0"=>"SMFBORNLGTAUTOX3"),
                        array("val0"=>"SMSWSGOGIXLAUTOX100"),
                        array("val0"=>"SMRQSAMGFEMX3"),
                        array("val0"=>"AYPOCBSL10SO"),
                        array("val0"=>"VPSZBVLCLON"),
                        array("val0"=>"AYPDAHDM18"),
                        array("val0"=>"BGCGES-1211B"),
                        array("val0"=>"BGCGES-1203O"),
                        array("val0"=>"SMRKPMCFEMX6"),
                        array("val0"=>"CMCEVP15W"),
                        array("val0"=>"SMSWSBCBAUTOX25"),
                        array("val0"=>"BGCGCH-001P"),
                        array("val0"=>"SMFBCMCKSAUTOX3"),
                        array("val0"=>"SMFBOROGKAUTOX3"),
                        array("val0"=>"TXOZESGM"),
                        array("val0"=>"SMDPC-ZFEMX3"),
                        array("val0"=>"FRTCPK13-141LT"),
                        array("val0"=>"FRBCCCGROW1LT"),
                        array("val0"=>"BGPMGKHNCL"),
                        array("val0"=>"BGBLKM4AMBER"),
                        array("val0"=>"PPPMGKZLNG/BL"),
                        array("val0"=>"BGPR2TRVBB"),
                        array("val0"=>"BGCGBK-800YE"),
                        array("val0"=>"SMSNNTLPXLAUTOX100"),
                        array("val0"=>"SMPSGXFEMX3+1"),
                        array("val0"=>"TOGGFDC125x400"),
                        array("val0"=>"FRGBGUESC300GR"),
                        array("val0"=>"FRBBRJ20LT"),
                        array("val0"=>"CMGGEXC200MM"),
                        array("val0"=>"FRBBAV20LT"),
                        array("val0"=>"BGBL589PINK"),
                        array("val0"=>"AYPSCDQC240ML"),
                        array("val0"=>"TXCOTELEBKWA"),
                        array("val0"=>"TXCOLBBPBLU"),
                        array("val0"=>"BACOMRTBLSM"),
                        array("val0"=>"TXBLWHTSHSLL"),
                        array("val0"=>"TXOZMCC"),
                        array("val0"=>"FRBBAM5LT"),
                        array("val0"=>"AYPZPHERSW"),
                        array("val0"=>"AYPZPIROSTO"),
                        array("val0"=>"AYPZPSKUDE"),
                        array("val0"=>"AYPZPALIDE"),
                        array("val0"=>"ESCTGRFOG1ML"),
                        array("val0"=>"SMSSWWAUTOX100"),
                        array("val0"=>"TXOZESGGR"),
                        array("val0"=>"TXCOSFTYE"),
                        array("val0"=>"BGBLK274TEAL"),
                        array("val0"=>"VPPFPEAKGLASS"),
                        array("val0"=>"VPCLVFLBG"),
                        array("val0"=>"FRWLRAVEA250ML"),
                        array("val0"=>"TXRTPPGR"),
                        array("val0"=>"MBGHPPROPOT1.5LT"),
                        array("val0"=>"SMSNSSKAUTOX10"),
                        array("val0"=>"SMSBKWWFEMX5"),
                        array("val0"=>"SMSBKCCMAUTOX5"),
                        array("val0"=>"SMRKGOSREGX10"),
                        array("val0"=>"SMRKDKAUTOX102X1"),
                        array("val0"=>"SMRKDKAUTOX10"),
                        array("val0"=>"SMRKCDAUTOX10"),
                        array("val0"=>"SMPSBLPFEMX3+1"),
                        array("val0"=>"SMSMMOYEXLAUTOX3"),
                        array("val0"=>"SMSMCOKIFEMX3"),
                        array("val0"=>"SMFBCMCKSAUTOX5"),
                        array("val0"=>"SMFBCBDAUTOX3"),
                        array("val0"=>"VPPFPPBALLCAP"),
                        array("val0"=>"AYPSHNTIGST"),
                        array("val0"=>"8436592320516"),
                        array("val0"=>"BGBLA13WHITE"),
                        array("val0"=>"SMSWRTXLAUTOX5+2"),
                        array("val0"=>"AYPRNEE2"),
                        array("val0"=>"SMSWSDDRFAUTOX25"),
                        array("val0"=>"PPAHUMSRO"),
                        array("val0"=>"SMSWSJK47XLAUTOX5+2"),
                        array("val0"=>"SMRVDSDFEMX3"),
                        array("val0"=>"SMRVWRFEMX3"),
                        array("val0"=>"SMRVSLAUTOX3"),
                        array("val0"=>"SMFBZKTLZAUTOX1"),
                        array("val0"=>"TXOZESGC"),
                        array("val0"=>"AYPBLRSNE8OZ"),
                        array("val0"=>"AYPBLRSNE4OZ"),
                        array("val0"=>"VPDVIQ2BLU"),
                        array("val0"=>"FRMRTRK1GR"),
                        array("val0"=>"FRBCYC1LT"),
                        array("val0"=>"FRANFF1LT"),
                        array("val0"=>"WAXPMGMBWCL"),
                        array("val0"=>"BGPMGKTCMF"),
                        array("val0"=>"SMSNSSFG1FEMX3"),
                        array("val0"=>"SMSNSSLHZFEMX3"),
                        array("val0"=>"PPICLQDRAWPM"),
                        array("val0"=>"PPICLQDRAWPLT"),
                        array("val0"=>"PPICLQDRAWMR"),
                        array("val0"=>"BGPMGKRVDF"),
                        array("val0"=>"BGPMGKHRDF"),
                        array("val0"=>"FRBCCCBLOOM1LT"),
                        array("val0"=>"SMDPMZFEMX3"),
                        array("val0"=>"VPSZBMGT"),
                        array("val0"=>"VPVVWPVALT"),
                        array("val0"=>"FRBCFS60ML"),
                    );
                    
        foreach($products as $product){
            
         
            $this->add_product($product["val0"]);   
        }
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
	
	function get_product_prices(){
	    
	    set_time_limit(0);
        $date = Date('Y-m-d');
		$date .= " 00:00:00";
	    
	    $productos = DB::table('descuentos')
	                ->distinct()
	                ->where('id_dlds', '!=', "")
	                ->where('precio', '=', "") 
	                ->limit(50)
	                ->get();
	    
	    
	    foreach($productos as $producto){
	   
	        $this->sync_descuentos_by_sku($producto->sku);
	       
	    }
	    
	}
	
	public function sync_descuentos_by_sku($sku){
	    
	   
	   set_time_limit(0);
	    
	   $productos = DB::table('descuentos')
	                                    ->where('sku', $sku)
	                                    ->get();
	    
       $web_service_dlds = new PrestaShopWebservice(
            $this->THIRD_PARTY_BASE_URL,
            $this->THIRD_PARTY_API_KEY,
            false
        );
	    
	    foreach($productos as $producto){
			
			try{
			    
			    //echo "Producto: ".$producto->sku;
			    
			    $productXML = $web_service_dlds->get([
                    "url" =>
                    $this->THIRD_PARTY_BASE_URL."/api/products/".$producto->id_dlds,
                ]);
                
                $specificPriceRules = $web_service_dlds->get([
                    "url" =>
                    $this->THIRD_PARTY_BASE_URL."/api/specific_prices&filter[id_product]=".$producto->id_dlds,
                ]);
                
   
                $priceReduction = 0;
                $date = Date('Y-m-d');
    			$date .= " 00:00:00";
    			
    			$specific_price = \DB::connection('dlds')->table('r3pa_specific_price')->select('*')->where('id_product', $producto->id_dlds)->where('to', ">=", $date)->where('from', "<=", $date)->orderBy('to', 'desc')->orderBy('reduction', 'desc')->first();
                
    			
    			foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
    			    
        			        
        			    $specialPriceData = $web_service_dlds->get([
                            "url" =>
                            $this->THIRD_PARTY_BASE_URL."/api/specific_prices/".$specificPriceIds['id'],
                        ]);
        				
        		// 		we know that Group 26 is the Franquicias group in DLDS, which is the one that Astro belongs to
        				if((int)$specialPriceData->specific_price->id_group === $this->id_group && $date >= $specialPriceData->specific_price->from && $date <= $specialPriceData->specific_price->to){
        
        		            // get the reduction assigned to this Group
        		            if((float)$specialPriceData->specific_price->reduction > $priceReduction){
        		                
        		                $priceReduction = (float)$specialPriceData->specific_price->reduction;
        		                //echo "Price reduction 1: ".$priceReduction."<br>";
        		            }
        					
        				}
        				
        			    if((int)$specialPriceData->specific_price->id_group === $this->id_group && $specialPriceData->specific_price->to == "0000-00-00 00:00:00"){
        
        		            //get the reduction assigned to this Group
        		
        		            if((float)$specialPriceData->specific_price->reduction > $priceReduction){
        		                
        		                $priceReduction = (float)$specialPriceData->specific_price->reduction;
        		                echo "Price reduction 1: ".$priceReduction."<br>";
        		            }
        					
    
        				}
    			
		    

    			}
			    
			    			
    			$specificPriceRules = $web_service_dlds->get([
                    "url" =>
                    $this->THIRD_PARTY_BASE_URL."/api/specific_prices&filter[id_product]=" . $producto->id_dlds."&&filter[id_group]=0?sort=[reduction_desc,to_desc]",
                ]);
                
                		    $priceReduction2 = 0;
    		    $id_reduction = 0;
    		    
    		    			    
    		    
    		    foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
    		        
    			    $specialPriceData = $web_service_dlds->get([
                        "url" =>
                        $this->THIRD_PARTY_BASE_URL."/api/specific_prices/".$specificPriceIds['id'],
                    ]);
                    
                    // echo "<br>DATE: ".$date." - FROM: ".$specialPriceData->specific_price->from." - TO: ".$specialPriceData->specific_price->to."<br><br>";
                    
                    
                    
    			    
    			    if($date >= $specialPriceData->specific_price->from && $date <= $specialPriceData->specific_price->to){
                        
                        // echo "Price reduction old: ".$priceReduction." - vs reduction: ".$specialPriceData->specific_price->reduction."<br>";

    			        if($specialPriceData->specific_price->reduction > $priceReduction || $priceReduction == 0){
    			           
    			             $priceReduction2 = (float)$specialPriceData->specific_price->reduction;
    			             
    			             echo "<br>Primera condicion: ".$priceReduction2;
    			        }
    			       
    				        
    				 }else{
    				        
    				    if($specialPriceData->specific_price->to == "0000-00-00 00:00:00"){
    
    				// 			get the reduction assigned to this Group
    				
    				            if((float)$specialPriceData->specific_price->reduction > $priceReduction){
    				                
    				                $priceReduction2 = (float)$specialPriceData->specific_price->reduction;
    				                echo "<br>Segunda condicion: ".$priceReduction."<br>";
    				            }
    						
    					}
    			        
    			        
    			    }
    			  
    				
    			}
    	    
    	    
    			if( $priceReduction2 && $priceReduction2 > $priceReduction){
    			     
    			     //echo "Level 2<br>";
    			     
    			     $priceReduction = $priceReduction2;
    			 }  
    	
    		
    		    if(!$priceReduction){
    	    
    							
        		    $specificPriceRules = $web_service_dlds->get([
                        "url" =>
                        $this->THIRD_PARTY_BASE_URL.'/api/specific_prices&filter[id_product]=' . $producto->id_dlds.'&&filter[to]=[0000-00-00]?sort=[reduction_desc]',
                    ]);
                    
        		    $priceReduction3 = 0;
        		    $id_reduction_3 = 0;
        		    
        		    foreach ($specificPriceRules->specific_prices->specific_price as $specificPriceIds){
        			    $specialPriceData = $web_service_dlds->get([
                            "url" =>
                            $this->THIRD_PARTY_BASE_URL."/api/specific_prices/".$specificPriceIds['id'],
                        ]);
    			    
        			     $priceReduction3 = (float)$specialPriceData->specific_price->reduction;
        			     $id_reduction_3 = $specialPriceData->specific_price->reduction->id;
        			     
        			     echo "ID REDUCTION: ".$id_reduction_3;
        			     break;
        			}
        			    
        			    
        			 if($priceReduction3 > $priceReduction){
        			     
        			     echo "Level 3 (".$priceReduction.")<br> ";
        			     
        			     $priceReduction = $priceReduction3;
        			 } 					    
        		}
    					
    					
    			//echo "<br>Price reduction: ".$priceReduction."<br><br>";
    			
    
    			$price = (float)$productXML->product->price;
    
    			//si hay valor en reducción, se lo resto al precio, sino, el precio es el que retornó la api
    			$totalProductPrice = (float)($priceReduction ? 
    									round(($price - ($price * $priceReduction)), 0, PHP_ROUND_HALF_UP) : $price);
    
    			//insertar en columna de "costo" de la tabla de productos_fulfillment
    		 	//$res = DB::table('productos_fulfillment')->where('sku', $sku)->update(['costo' => $totalProductPrice]);
    		 	
    		 	$res = DB::table('descuentos')->where('sku', $sku)->update(['precio' => $totalProductPrice]);
    		 	       DB::table('descuentos')->where('sku', $sku)->update(['descuento' => $priceReduction]);
    
    		 	
    			//echo 'UPDATE productos_fulfillment SET costo = ' . $totalProductPrice . ' WHERE sku = "' . $producto->sku . '";<br>';
    			//echo '<tr><td>'.$row['sku'].'</td><td>'.$totalProductPrice.' - Reduction: '.$priceReduction.'</td></tr>';
                //echo "Total product price: ".($price - ($price*$priceReduction));
                
    //             if($res){
    			    
    // 			    echo $totalProductPrice;
    			    
    // 			}else{
    			    
    // 			    echo "RES: ".$res;
    // 			}

			    
			}catch(Exception $ex){
			    
			    echo "Error: ".$ex->getMessage();
			    
			    var_dump($ex);
			}
	        
	       // DB::table('productos_fulfillment')
	       //     ->where('id', $producto->id)
        //         ->update(['to_sync' => 0]);
	        
	    }
	    
	    
	}
	
}
