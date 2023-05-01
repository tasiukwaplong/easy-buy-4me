<?php

namespace App\Services;

use App\Models\Vendor;

/**
 * To receive errand request and send back the appropriete response
 * all functions in this class returns an array(message=>...., options=>....., button=> ...., image=> ...)
 * button, image and options are optional fields and need to be checked for
 * message is returned from all functions
 */

class ErrandService
{
    const ORDER_FOOD = '[Order Food]';
    const GROCERY_SHOPPING = '[Grocery Shopping]';
    const ITEM_PICK_UP = '[Item pick-up]';
    const OTHER_ITEMS = '[Other items]';
    const VENDORS = '[Vendors]';
    const CUSTOM = '[Custom]';

    //When user
    public function init()
    {
        $easyBuyLogo = "easybuylogo.com";
        $message = "I can help you run errands. \nEasyBuy4Me runs errands and make deliveries to your doorstep.\nCLick on the select option below to see the different kinds of errands I can help you run.";

        return array(
            'image' => $easyBuyLogo,
            'message' => $message,
            'button' => "MENU"
        );
    }

    public function getErrandServicesOptions()
    {
        $optionsAndDescription = array(
            self::ORDER_FOOD => "See the list of food vendors, foods and their pricing",
            self::GROCERY_SHOPPING => "See the list of places we can help you do your grocery shopping from.",
            self::ITEM_PICK_UP => "Need a pick-up and drop-off of an item",
            self::OTHER_ITEMS => "Other items such as accessories, clothes and pastries",
            self::VENDORS => "See the list of our vendors and what they do",
            self::CUSTOM => "Chat with a real person"
        );


        return array(
            'message' => 'Errand Services',
            'options' => $optionsAndDescription
        );
    }


    public function getErrandService(String $key)
    {

        if ($key == self::ORDER_FOOD) {
            $vendors = self::getVendorsWithCategory('food');
            $arrToReturn = array('message' => "Order Food \nTap to select an item");
            $options = array();

            foreach ($vendors as $vendor) {
                $options["[Order from " . $vendor->name . "]"] = $vendor->description;
            }

            $arrToReturn["options"] = $options;
            return $arrToReturn;
        } elseif ($key == self::GROCERY_SHOPPING) {
            $vendors = self::getVendorsWithCategory('grocery');
            $arrToReturn = array('message' => "Get groceries around you\nTap to select an item");
            $options = array();

            foreach ($vendors as $vendor) {
                $options["[Order from " . $vendor->name . "]"] = $vendor->description;
            }

            $arrToReturn["options"] = $options;
            return $arrToReturn;
        } elseif ($key == self::ITEM_PICK_UP) {

            return array(
                'message' => "Contact our agent if you are in need fof someone to help you run an errand"
            );
        } elseif ($key == self::OTHER_ITEMS) {

            $vendors = self::getVendorsWithCategory('other');
            $arrToReturn = array('message' => "Order Plethora of items around you \nTap to select an item");
            $options = array();

            foreach ($vendors as $vendor) {
                $options["[Order from " . $vendor->name . "]"] = $vendor->description;
            }

            $arrToReturn["options"] = $options;
            return $arrToReturn;
        } elseif ($key == self::VENDORS) {

            $vendors = Vendor::all();
            $arrToReturn = array('message' => "Order Plethora of items around you \nTap to select an item");
            $options = array();

            foreach ($vendors as $vendor) {
                $options["[Order from " . $vendor->name . "]"] = $vendor->description;
            }

            $arrToReturn["options"] = $options;
            return $arrToReturn;
        } elseif ($key == self::CUSTOM) {

            return array(
                'message' => "You can chat with our customer care agent through this 089009101"
            );
        } else {

            return array(
                'message' => "The response you entered was not understood"
            );
        }
    }

    public function getVendorCatalog(String $vendorId)
    {
        $vendor = Vendor::find($vendorId);
        $items = $vendor->items()->get();
        $message = $vendor->name . "\n";

        $index = 1;
        foreach ($vendor->items as $item) {
            $message = $message . "\n " . $index . ". " . $item->item_name . " - " . $item->item_price . " per " . $item->unit_name . " \n";
        }

        return array(
            'image' => $vendor->imageUrl,
            'message' => $message,
            'buttons' => ["Go Back", "Support"]
        );
    }

    private function getVendorsWithCategory(String $category)
    {
        return Vendor::with(['items' => function ($query) use ($category) {
            $query->where('category', $category);
        }])->get(); //an obeject which contains vendor details and list of items
    }
}
