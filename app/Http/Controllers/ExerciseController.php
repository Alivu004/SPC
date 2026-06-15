<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
class ExerciseController extends Controller
{
    public function exercise1Artwork(Request $request){

        $validator = Validator::make($request->all(), [
        'input' => 'required|array',
        'input.*.id' => 'required',
        'input.*.approved' => 'required|boolean',
        'input.*.rejected' => 'required|boolean',
        'input.*.time' => 'required',
        ]);
         if ($validator->fails())
            return $this->simpleValidate($validator);

        try{
            $input = $request->input ?? [];
                $input = collect($input)->where('approved', true)
                ->where('rejected', false)
                ->whereNotNull('time')
                ->sortByDesc(function($item) {
                    return $item['time'];
                })->values();

                $data = $input ? $input->first() : null;
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $data['id']
                    ],
                    'error' => null
                ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => 'input data is not in expected format'
            ]);
        }

    }

    function simpleValidate($validator)
    {
        $error = $validator->errors()->first();
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => $error
        ], 422);
    }

    public function exercise2TierPricing(Request $request){

        $validator = Validator::make($request->all(), [
            'input' => 'required|array',
            'input.quantity' => 'required|integer',
            'input.tiers' => 'required|array',
            'input.tiers.*.min' => 'required|integer',
            'input.tiers.*.price' => 'required|numeric',
        ]);
        if ($validator->fails())
            return $this->simpleValidate($validator);
          $threshold = $request->input['quantity'] ?? 0;
          $tiers = $request->input['tiers'] ?? [];
          $finalValue = [];
          foreach($tiers as $key=>$tier) {
             if($threshold >= $tier['min']){
                $difference = $threshold - $tier['min'];
                if(!isset($finalValue['value'])){
                    $finalValue['index'] = $key;
                    $finalValue['value'] = $difference;
                }

                if(isset($finalValue['value']) && $difference <= $finalValue['value']){
                    $finalValue['index'] = $key;
                    $finalValue['value'] = $difference;
                }
             }
          }
          if(!isset($finalValue['index'])){
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => 'No valid tier found for the given quantity'
            ]);
          }
          return response()->json([
                'success' => true,
                'data' =>  [
                    'price' => $tiers[$finalValue['index']]['price'],
                ],
                'error' => null
            ]);
    }

    public function exercise3CartValidator(Request $request){
        $validator = Validator::make($request->all(), [
            'input' => 'required|array',
            'input.*.id' => 'required',
            'input.*.required' => 'required|boolean',
            'input.*.done' => 'required|boolean',
        ]);
        if ($validator->fails())
            return $this->simpleValidate($validator);
        $input = $request->input ?? [];
        $itemsList = collect($input)->where('required',true)
        ->where('done',false)
        ->pluck('id');
        $data['valid'] = count($itemsList) == 0 ? true : false;
        $data['invalid_items'] = $itemsList;
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null
        ]);

    }

    public function exercise4VendorAllocation(Request $request){
         $validator = Validator::make($request->all(), [
           "input" => "required|array",
            "input.order_qty" => "required|integer|min:1",
            "input.vendors" => "required|array|min:1",
            "input.vendors.*.id" => "required|integer",
            "input.vendors.*.stock" => "required|integer|min:0",
        ]);

        if($validator->fails())
            return $this->simpleValidate($validator);
          $orderQty = $request->input['order_qty'] ?? [];
          $remainingQty = $orderQty;
          $vendors = $request->input['vendors'] ?? [];
          $allocation = [];
          foreach($vendors as $vendor){
              if($remainingQty > 0){
                if($vendor['stock'] >= $remainingQty){
                    $allocation[] =[
                        'vendor_id' => $vendor['id'],
                        'allocated_qty' => $remainingQty
                    ];
                    $remainingQty = 0;
                    break;
                }
                $remainingQty = $remainingQty - $vendor['stock'];
                    $allocation[] =[
                        'vendor_id' => $vendor['id'],
                        'allocated_qty' => $vendor['stock']
                    ];
              }
          }
          $data = $allocation;
          return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null
        ]);
    }

    public function exercise5Discount(Request $request){
        $validator = Validator::make($request->all(), [
           "input" => "required|array",
            "input.price" => "required|numeric|min:0",
            "input.discounts" => "required|array|min:1",
            "input.discounts.*.type" => "required|string|in:percentage,flat",
            "input.discounts.*.value" => "required|numeric|min:0",
        ]);

        if($validator->fails())
            return $this->simpleValidate($validator);
         $price = $request->input['price'];
         $discounts = $request->input['discounts'] ?? [];
         $finalPrice = $price;
         foreach($discounts as $disc){
             $afterDiscountPrice = 0;
             $type = $disc['type'];
             if($type == 'percentage'){
                $afterDiscountPrice = $price - ($price * ($disc['value'] / 100));
             }elseif($type == 'flat'){
                $afterDiscountPrice = $price - $disc['value'];
             }

             if($afterDiscountPrice < $finalPrice){
                $finalPrice = $afterDiscountPrice > 0 ? $afterDiscountPrice : 0;
             }
         }
         return response()->json([
            'success' => true,
            'data' => [
                'final_price' => $finalPrice
            ],
            'error' => null
        ]);

    }

    public function exercise6ApprovalFlow(Request $request) {
        $validator = Validator::make($request->all(), [
            "input" => "required|array",
                "input.steps" => "required|array|min:1",
                "input.steps.*.id" => "required",
                "input.steps.*.depends_on" => "required",
            ]);

            if($validator->fails())
                return $this->simpleValidate($validator);
        $steps = $request->input['steps'] ?? [];
        $valid = false;

        foreach ($steps as $key => $step) {
            $depends_on = $step['depends_on'] ?? null;

            if ($key == 0) {
                $valid = !$depends_on;
                if (!$valid) break;
                continue;
            }

            $prevStep = $steps[$key - 1] ?? null;
            if (!$prevStep || !isset($prevStep['id'])) {
                $valid = false;
                break;
            }

            if ($prevStep['id'] == $depends_on) {
                $valid = true;
            } else {
                $valid = false;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'data'    => ['valid' => $valid],
            'error'   => null
        ]);
    }

    public function exercise7Inventory(Request $request){
        $validator = Validator::make($request->all(), [
            "input" => "required|array",
                "input.stock" => "required|numeric|min:1",
                "input.requests" => "required|array|min:1",
                "input.requests.*" => "required|numeric|min:1",
            ]);

            if($validator->fails())
                return $this->simpleValidate($validator);
        $stock = $request->input['stock'];
        $remaingStock = $stock;
        $data = [];
        $requests = $request->input['requests'];
        foreach($requests as $req){
           if($remaingStock >= $req){
            $remaingStock = $remaingStock -  $req;
              $data[]=true;
           }else{
                $data[]=false;
           }
        }
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null
        ]);
    }

    public function exercise8Shipment(Request $request){
         $validator = Validator::make($request->all(), [
            "input" => "required|array",
                "input.ordered" => "required|numeric|min:1",
                "input.shipped" => "array",
                "input.shipped.*" => "required|numeric|min:0",
            ]);
            if($validator->fails()){
                return $this->simpleValidate($validator);

            }
        $ordered = $request->input['ordered'];
        $shipped = collect($request->input['shipped'])->sum();
        $remaining = $ordered - $shipped;
        return response()->json([
            'success' => true,
            'data' => [
                'remaining' => $remaining
            ],
            'error' => null
        ]);

    }

    public function exercise9Webhook(Request $request){
           $validator = Validator::make($request->all(), [
            "input" => "required|array",
                "input.*id" => "required",
                "input.*time" => "required",
            ]);
            if($validator->fails()){
                return $this->simpleValidate($validator);

            }
           $wbhooks = $request->input;
           $webhookIds = collect($wbhooks)->pluck('id')->unique()->values();
           return response()->json([
            'success' => true,
            'data' => $webhookIds,
            'error' => null
        ]);
    }

    public function exercise10QuoteExpiry(Request $request){

        $validator = Validator::make($request->all(), [
            "input" => "required|array",
                "input.created_at" => "required|date_format:Y-m-d|before_or_equal:input.current_date",
                "input.valid_days" => "required|numeric|min:1",
                "input.current_date" => "required|date_format:Y-m-d",
            ]);
            if($validator->fails()){
                return $this->simpleValidate($validator);

            }
          $createdAt = $request->input['created_at'];
          $validDays = $request->input['valid_days'];
          $currentDate = $request->input['current_date'];
          $createdAtDate = Carbon::parse($createdAt);
          $currentDateDate = Carbon::parse($currentDate);
          $diffInDays = $createdAtDate->diffInDays($currentDateDate);
          $expired = $diffInDays > $validDays ? false : true;
          $data['valid'] = $expired;
          return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null
        ]);
    }

    public function exercise11ProductVisibility(Request $request)
{

    $validator = Validator::make($request->all(), [
        'input' => ['required', 'array'],
        'input.customer.tags'=> ['required','array'],
        'input.products'=> ['required', 'array'],
        'input.products.*.id'=> ['required', 'integer'],
        'input.products.*.block'=> ['array'],
        'input.products.*.allow'=> ['array'],
    ]);
     if($validator->fails()){
                return $this->simpleValidate($validator);

            }
    $customerTags = $request->input['customer']['tags'] ?? [];
    $visibleProducts = [];
    $isBlocked  = false;
    $isAllowed  = false;
    $allowedProducts = [];
    $products = $request->input['products'] ?? [];

    foreach ($products as $product) {
        $isBlocked = false;
        $isAllowed = false;

        if (count($product['block']) > 0) {
            foreach ($product['block'] as $block) {
                if (in_array($block, $customerTags)) {
                    $isBlocked = true;
                    break;
                }
            }
        }

        if (count($product['allow']) > 0) {
            foreach ($product['allow'] as $allow) {
                if (in_array($allow, $customerTags)) {
                    $isAllowed       = true;
                    $allowedProducts[] = $product['id'];
                    break;
                }
            }
        }

        if (!$isBlocked && $isAllowed) {
            $visibleProducts[] = $product['id'];
        }
    }

    return response()->json([
        'success' => true,
        'data'    => [
            $visibleProducts,
        ],
        'error'   => null,
    ]);
}

    public function exercise12BundlePricing(Request $request)
{
    $validator = Validator::make($request->all(), [
        'input'=> ['required', 'array'],
        'input.items' => ['required', 'array'],
        'input.items.*.price' => ['required', 'numeric'],
        'input.items.*.id'  => ['required', 'integer'],
        'input.bundle_price'  => ['required', 'numeric'],
        'input.apply_bundle'  => ['required', 'boolean'],
    ]);

     if($validator->fails()){
                return $this->simpleValidate($validator);
     }

    $items       = $request->input['items'] ?? [];
    $itemSum     = collect($items)->pluck('price')->sum();
    $bundlePrice = $request->input['bundle_price'] ?? 0;
    $applyBundle = $request->input['apply_bundle'];
    $finalPrice  = 0;

    if ($applyBundle && $itemSum > $bundlePrice) {
        $finalPrice = $bundlePrice;
    } else {
        $finalPrice = $itemSum;
    }

    return response()->json([
        'success' => true,
        'data'    => [
            'final_price' => $finalPrice,
        ],
        'error'   => null,
    ]);
}

    public function exercise13CartMerge(Request $request){
        $validator = Validator::make($request->all(), [
            "input" => "required|array",
                "input.guest" => "nullable|array",
                "input.user" => "nullable|array",
            ]);
            if($validator->fails()){
                return $this->simpleValidate($validator);

            }
           $guestItems = $request->input['guest'] ?? [];
           $userItems = $request->input['user'] ?? [];
           $mergedItems = collect($guestItems)->merge($userItems)->groupBy('id')->map(function($item){
                 return [
                      'id' => $item->first()['id'],
                      'quantity' => $item->sum('qty')
                 ];
            })->values();

          return response()->json([
            'success' => true,
            'data' => $mergedItems,
            'error' => null,
        ]);

    }

    public function exercise14Upsell(Request $request){
        $validator = Validator::make($request->all(), [
            "input" => "required|array",
                "input.nums" => "required|array|min:2",
                "input.nums.*" => "required|numeric",
                "input.target" => "required|numeric",
            ]);
            if($validator->fails()){
                return $this->simpleValidate($validator);

            }
        $numbers =$request->input['nums'] ?? [];
        $target = $request->input['target'] ?? 0;

        $data = [];
        foreach($numbers as $key=>$num){
               if(!isset($numbers[$key + 1])) continue;
               $sum = $num + $numbers[$key + 1];
               if($sum == $target){
                $data[] = [$key, $key + 1];
                break;
               }
               $numberLength = count($numbers) - ($key + 1);
               $numbersNewIndex = $key + 1;
               while($numberLength > 0){
                   $sum = $num + $numbers[$numbersNewIndex];
                   if($sum == $target){
                    $data[] = [$key, $numbersNewIndex];
                    break 2;
                   }
                   $numbersNewIndex++;
                   $numberLength--;
               }
        }
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ]);
    }

    public function exercise15ShippingRule(Request $request){

            $validator = Validator::make($request->all(), [
                "input" => "required|array",
                    "input.order" => "required",
                    "input.order.weight" => "required|numeric|min:0",
                    "input.order.country" => "required|string",
                    "input.rules" => "required|array|min:1",
                    "input.rules.*.max_weight" => "numeric|min:0",
                    "input.rules.*.country" => "string",
                    "input.rules.*.method" => "required|string",
                    "input.rules.*.priority" => "required|integer|min:1",
                ]);
                if($validator->fails()){
                    return $this->simpleValidate($validator);

                }
           $input = $request->input['order'] ?? [];
           $rules = $request->input['rules'] ?? [];
           $orderWeight = $input['weight'] ?? 0;
           $orderCountry = $input['country'] ?? '';
           $getRules = [];

           foreach($rules as $rule){
                $maxWeight = $rule['max_weight'] ?? 0;
                if($maxWeight >= $orderWeight || $orderCountry== ($rule['country'] ?? '')){
                    $getRules[] = $rule;
                }
           }
              $data = $getRules;
              $data = collect($data)->sortBy('priority')->values()->first();

            return response()->json([
                'success' => true,
                'data' => $data['method'] ?? null,
                'error' => null,
            ]);






    }

    public function exercise16FraudCheck(Request $request){
            $validator = Validator::make($request->all(), [
                "input" => "required|array",
                    "input.order" => "required",
                    "input.order.amount" => "required|numeric|min:0",
                    "input.order.country" => "required|string",
                    "input.rules" => "required|array|min:1",
                    "input.rules.*.max_amount" => "numeric|min:0",
                    "input.rules.*.blocked_countries" => "array",
                    "input.rules.*.blocked_countries.*" => "string",
                ]);
                if($validator->fails()){
                    return $this->simpleValidate($validator);

                }
         $input = $request->input['order'] ?? [];
         $rules = $request->input['rules'] ?? [];
         $amount = $input['amount'] ?? 0;
         $orderCountry = $input['country'] ?? '';
         $data = ['flagged' => false];
        //  dd($amount, $orderCountry, $rules);
        if($amount >= $rules['max_amount']){
           $blockedCountries = $rules['blocked_countries'] ?? [];
           if(in_array($orderCountry, $blockedCountries)){
                $data['flagged'] = true;
           }else{
                $data['flagged'] = false;
           }
         }
         return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ]);
    }

    public function exercise18DataSync(Request $request){
            $validator = Validator::make($request->all(), [
                "input" => "required|array",
                    "input.shopify" => "required|array",
                    "input.shopify.updated_at" => "required|numeric",
                    "input.internal" => "required|array",
                    "input.internal.updated_at" => "required|numeric",
                ]);
                if($validator->fails()){
                    return $this->simpleValidate($validator);

                }
            $shopify = $request->input['shopify'] ?? [];
            $internal = $request->input['internal'] ?? [];
            if($shopify['updated_at'] > $internal['updated_at']){
                $data='shopify';
            }else {
                $data = 'internal';
            }
            return response()->json([
                'success' => true,
                'data' => $data,
                'error' => null,
            ]);
    }
    // public function exercise17ShopifyPriceAdjustment(Request $request){
    //      $validator = Validator::make($request->all(), [
    //         "input" => "required",
    //         "input.adjustment_value" => "required|numeric",
    //         "input.prices" => "required|array",
    //         "input.prices.*" => "required|array",
    //     ]);
    //     if($validator->fails()){
    //         return $this->simpleValidate($validator);

    //     }
    //     $prices = $request->input['prices'] ?? [];
    //     $x = $request->input['adjustment_value'] ?? 0;


    //     $flatPrices = [];

    //     foreach ($prices as $row) {
    //     foreach ($row as $price) {
    //     $flatPrices[] = $price;
    //     }
    //     }


    //     $base = $flatPrices[0];

    //     foreach ($flatPrices as $price) {


    //     if (abs($price - $base) % $x != 0) {

    //     return response()->json([
    //     'success' => true,
    //     'data' => [
    //     'minimum_operations' => -1
    //     ]
    //     ]);
    //     }
    //     }

    //     sort($flatPrices);

    //     $n = count($flatPrices);
    //     $median = $flatPrices[intval($n / 2)];


    //     $operations = 0;

    //     foreach ($flatPrices as $price) {
    //     $operations += abs($price - $median) / $x;
    //     }

    //     return response()->json([
    //     'success' => true,
    //     'data' => [
    //     'minimum_operations' => $operations
    //     ]
    //     ]);
    // }

    public function exercise19VariantControl(Request $request){
          $validator = Validator::make($request->all(), [
            "input" => "required|array",
            "input.options" => "required|array|min:1",
            "input.options.*.values" => "required|numeric|min:1",
            "input.limit" => "required|numeric|min:1",
        ]);
        if($validator->fails()){
            return $this->simpleValidate($validator);
        }
          $options = $request->input['options'] ?? [];
          $limit = $request->input['limit'] ?? 0;
          $total = 1;
          foreach($options as $key => $option){
               $total = $option['values'] * $total;
          }
          return response()->json([
            'success' => true,
            'data' => [
                'total_combinations' => $total,
                'limit_exceeded' => $limit <= $total ? true : false,
            ],
            'error' => null,
        ]);

    }

    public function exercise20OrderState(Request $request){
            $validator = Validator::make($request->all(), [
                "input" => "required|array",
                "input.transitions" => "required|array|min:1",
                "input.transitions.*" => "in:created,paid,processing,shipped,delivered",
            ]);
            if($validator->fails()){
                return $this->simpleValidate($validator);
            }
          $transitions = $request->input['transitions'] ?? [];
          $sequence = ['created', 'paid', 'processing', 'shipped', 'delivered'];

          foreach($transitions as $key=>$transition){
            if($sequence[$key] == $transition){
                continue;
            }else {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'valid' => false,
                    ],
                    'error' => null,
                ]);
            }


          }
          return response()->json([
              'success' => true,
              'data' => [
                  'valid' => true,
              ],
              'error' => null,
          ]);


    }


}
