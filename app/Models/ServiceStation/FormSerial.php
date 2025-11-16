<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FormSerial extends Model
{
    use HasFactory;

    protected $table = "form_serials";

    public static function getTemporaryId($form_type){

        $serialNo   =   self::getSerialNumber($form_type);
        return self::validateSerial($serialNo,$form_type);
    }

    protected static function getSerialNumber($form){

        $formSerial = new FormSerial();

        $res = $formSerial::where('form','=',$form)->get();

        if(isset($res[0])){
            return $res[0]->prefix.'-'.$res[0]->num;
        }else{
            return "";
        }

    }

    protected static function updateSerial($form){

        $upData =[
            'num' => DB::raw('num+1')
        ];
        self::where('form',$form)->update($upData);
    }

    protected static function validateSerial($serialNo,$form){

        while(self::checkSerial($serialNo,$form)==false){

            self::updateSerial($form);
            $serialNo   =   self::getSerialNumber($form);
        }

        return $serialNo;
    }

    protected static function checkSerial($serialNo,$form){

        if($form === 'work_order'){$func="getWorkOrderList";}
        if($form === 'job_order'){$func="getJobOrderList";}

        $res    =   self::{$func}($serialNo);


        if(count($res)>0){
            return false;
        }else{
            return true;
        }
    }

    protected static function getWorkOrderList($serial){

        return WorkOrder::where('serial_no', $serial)->get();
    }

    protected static function getJobOrderList($serial){

        return JobOrder::where('serial_no', $serial)->get();
    }

    public static function getSerialNumber2($form){

        $res = self::where('form',$form)->first();

        if($res->count() > 0){
            return $res;
        }else{
            return "";
        }

    }

    public static function getSerialData($request){

        /*Develop & added By Dinesh Lakmal
        2017-12-19*/
        try {

            $form_type = ($request->form_type);

            $serialDetails = self::getSerialNumber2($form_type);
            $serial_pre = $serialDetails->prefix;

            if (empty($serial_pre) || $serial_pre == null)
                $serial_index = "";
            else
                $serial_index = $serial_pre;

            $serialNo = $serialDetails->prefix . '-' . $serialDetails->num;

            $realSerial = self::validateSerial($serialNo, $form_type);

            $real_serial = str_replace($serial_index, "", $realSerial);
            $real_array[0] = array();

            $real_array[0]['start'] = $serial_index;
            $real_array[0]['num'] = $real_serial;

            if (isset($real_array[0])) {

                return response()->json([ 'status'   => true, 'data' => $real_array, 'message'  => ['success'], 'statusCode' => 200],200);
            }

            return response()->json(['status' => false, 'data' => $real_array, 'message' => ['Not Found'], 'statusCode' => 404], 404);
        }catch (\Exception $e){
            Log::error("getFormSerialError:".$e->getMessage());
            return response()->json([ 'status'   => false, 'message'  => ['Somethings went wrong'], 'statusCode' => 500],500);
        }
    }

}
