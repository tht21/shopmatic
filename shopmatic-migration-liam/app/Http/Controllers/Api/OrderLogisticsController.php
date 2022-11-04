<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderLogisticsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->input();

        $logistic = $input['logistic'];

        // Validation
        $validatorData = [
            'order_items' => 'required',
            'weight' => 'required|numeric|min:0|not_in:0',
            'parcel_id' => 'required|string',
        ];

        if($logistic && $logistic['service_type'] == 1) {

            $validatorData['from_country'] = 'required|string';
            $validatorData['from_state'] = 'required|string';
            $validatorData['from_postcode'] = 'required|numeric';
        }

        $validator = Validator::make($input, $validatorData);

        if ($validator->fails()){
            return $this->showValidationError($validator);
        }

        return $this->respond($input);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
