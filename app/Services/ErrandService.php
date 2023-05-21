<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Vendor;

class ErrandService
{

    const ORDER_FOOD = '[errand-order-food]';
    const GROCERY_SHOPPING = '[errand-grocery-shopping]';
    const ITEM_PICK_UP = '[errand-item-pick-up]';
    const OTHER_ITEMS = '[errand-other-items]';
    const VENDORS = '[errand-vendors]';
    const CUSTOM = '[errand-custom]';

    //When user
    public function init()
    {
        $easyBuyLogo = "easybuylogo.com";
        $message = "I can help you run errands. \nEasyBuy4Me runs errands and make deliveries to your doorstep.\n\nTap *MENU* below to see the different kinds of errands I can help you run.";

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


    public function getErrandService(string $key, $easylunch = false)
    {

        if ($key == self::ORDER_FOOD) {
            $vendors = self::getVendorsWithCategory('food');
            $arrToReturn = array('message' => "Order Food \nTap to select an item");
            $options = array();

            foreach ($vendors as $vendor) {
                if($easylunch)
                $options["[Order from " . $vendor->name .":easylunch" . "]"] = $vendor->description;
                else
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
                'message' => "Please, contact our agent if you are in need for someone to help you run an errand"
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
            $arrToReturn = array('message' => "Checkout a plethora of vendors around you");
            $options = array();

            foreach ($vendors as $vendor) {
                $options["[vendor-" . $vendor->id . "]"] = [$vendor->name, $vendor->description];
            }

            $arrToReturn["options"] = $options;
            return $arrToReturn;

        } elseif ($key == self::CUSTOM) {

            $text = "Hello...";

            return array(
                'message' => "You can chat with our customer care agent through \nhttps://wa.me/2347035002025?text=$text"
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
       
        return array(
            'image' => $vendor->imageUrl,
            'items' => $items,
            'buttons' => ["Go Back", "Support"]
        );
    }

    private function getVendorsWithCategory(String $category)
    {

        $vendors = Item::where('category', $category)
            ->get()
            ->map(function ($item) {
                return $item->vendor;
            })->all();

        return $vendors;
    }
}
