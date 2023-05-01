<?php

namespace App\Services;
use App\Models\Vendor;

/**
 * To receive errand request and send back the appropriete response
 */

class ErrandService {
    const ORDER_FOOD = '[Order Food]';
    const GROCERY_SHOPPING = '[Grocery Shopping]';
    const ITEM_PICK_UP = '[Item pick-up]';
    const OTHER_ITEMS = '[Other items]';
    const VENDORS = '[Vendors]';
    const CUSTOM = '[Custom]';

    //When user
    public function init(){
        $easyBuyLogo = "easybuylogo.com";
        $message = "I can help you run errands. \nEasyBuy4Me runs errands and make deliveries to your doorstep.\nCLick on the select option below to see the different kinds of errands I can help you run.";

        return array(
            'logo' => $easyBuyLogo,
            'message' => $message,
            'button' => "MENU"
        );
    }

    public function getErrandServicesOptions(){
        $optionsAndDescription = array(
            ORDER_FOOD => "See the list of food vendors, foods and their pricing",
            GROCERY_SHOPPING => "See the list of places we can help you do your grocery shopping from.",
            ITEM_PICK_UP => "Need a pick-up and drop-off of an item",
            OTHER_ITEMS => "Other items such as accessories, clothes and pastries",
            VENDORS => "See the list of our vendors and what they do",
            CUSTOM => "Chat with a real person"
        );


        return array(
            'message' => 'Errand Services',
            'optionsAndDesciption' => $optionsAndDescription 
        );
    }

    public function getErrandService(String $key){
        
        if($key == ORDER_FOOD){
            $vendors = getVendorsWithCategory('food');
            $arrToReturn = array('message'=>"Order Food \nTap to select an item");
            $options = array();
            
            foreach ($vendors as $vendor) {
                $options["[Order from ".$vendor->name."]"] = 
            }

            $arrToReturn["options"] = $options;
            return $arrToReturn;
        }elseif ($key == GROCERY_SHOPPING) {
            
        }elseif ($key == ITEM_PICK_UP) {
            
        }elseif ($key == OTHER_ITEMS) {
            
        }elseif ($key == VENDORS) {
            
        }elseif ($key == CUSTOM) {
            
        }else{
            return array(
                'message' => "The response you entered was not understood"
            );
        }
    }

    private function getVendorsWithCategory(String $category){
        return Vendor::with(['items' => function ($query) {
            $query->where('category', $category);
        }])->get();//an obeject which contains vendor details and list of items
    }
}