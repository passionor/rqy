<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation;
use Illuminate\Support\Facades\Hash;

/**
 * 手机端接口，目前保证能用，不做其他处理
 */

class ApiController extends Controller{


    /**
     * 登录
     */
    public function app_login(Request $request){
        $tel = $request->tel;
        $password = $request->password;
        if(!$tel || !$password){
            return response()->json('参数错误');
        }
        
        $t = DB::table('users')->where('tel','=',$tel)->get();
        if(!$t->count()){
            return response()->json('账号错误');
        }

        $p = DB::table('users')
        ->where('tel','=',$tel)
        ->select('password')
        ->first();

        if(!Hash::check($password,$p->password)){
            return response()->json('密码错误');
        }

        $info = DB::table('users')
        ->where('tel','=',$tel)
        ->select('id','name','password','email','tel','qq','wx','sex')
        ->first();

        return response()->json(["success"=>"true","info"=>$info]);
    }

    /**
     * 注册
     */
    public function app_register(Request $request){
        $tel = $request->tel;
        $name = $request->name;
        $email = $request->email;
        $password = $request->password;
        $qq = $request->qq;
        $wx = $request->wx;
        $sex = $request->sex;
        $type = '0';
        $ctime = date('Y-m-d H:i:s',time());
        $t = DB::table('users')->where('tel','=',$tel)->get();
        if($t->count()){
            return response()->json('此账号已被注册，请重新选择');
        }
        $Getid = DB::table('users')->insertGetId(
            [
                'tel'=>$tel,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'qq' => $qq,
                'wx' => $wx,
                'sex' => $sex,
                'type' => $type,
                'created_at' => $ctime,
                'updated_at' => $ctime
            ]
        );

        if ($Getid){
            return response()->json('success');
        }
    }

    /**
     * 重置密码
     */
    public function reset_password(Request $request){
        $tel = $request->tel;
        $old_password = $request->old_password;
        $new_password = $request->new_password;

        $res = DB::table('users')
        ->where('tel','=',$tel)
        ->select('password')
        ->first();

        if(!Hash::check($old_password, $res->password)){
            return response()->json('原密码错误');
        }
        $update = array(
          'password'  =>Hash::make($new_password),
        );
        $result = DB::table('users')
        ->where('tel',$tel)
        ->update($update);

        if($result){
            return response()->json('success');
        }else{
            return response()->json('修改失败，请重试');
        }



    }


    /**
     * 订单列表
     * status:0表未接单，1表示已接单，2表示订单已完成
     */
    public function order_list(Request $request){
        $wrap_type = $request->wrap_type;
        $platform = $request->platform;
        
        // if(empty($wrap_type)){
        //     return response()->json('参数错误');
        // }

        $builder = DB::table('order_record')
            ->select('serial','charge')
            ->where('wrap_type','=',$wrap_type)
            ->where('platform','=',$platform)
            ->where('status','=','0');

        $list = $builder->orderBy('ctime', 'desc')->get()->toArray();

         $data = [
            "data"=>$list,
        ];
        return response()->json($data);
        
    }

    /**
     * 接订单
     */
    public function order_receiving(Request $request){
        $serial = $request->serial;
        $buyer = $request->buyer;
        $id = $request->id;

        // 判断是否实名认证
        $cer = DB::table('certification')
            ->where('user_id','=',$id)
            ->where('status','=','2')
            ->first();
        
        if(!$cer){
            $data = [
                "status" => 'fail',
                "msg" => '您的账户还未实名认证，请先实名认证在重试！'
            ];
            return response()->json($data);
        }

        // 判断当前是否已经接单，同一时间段只允许接一单
        $order = DB::table('order_record')
            ->where('receiving_id','=',$id)
            ->whereIn('status',array(1,3))
            ->first();
        
        if($order){
            $data = [
                "status" => 'fail',
                "msg" => '您当前还有未完成的订单，请先完成订单！'
            ];
            return response()->json($data);
        }

        // if(empty($serial) || empty($buyer) || empty($id)){
        //     return response()->json('参数错误');
        // }

        $update = array(
            'receiving_id'=> $id,
            'buyer'  => $buyer,
            'status' => 1
        );

        $result = DB::table('order_record')
            ->where('serial','=',$serial)
            ->where('status','<>','1')
            ->update($update);

        if($result){
            $data = [
                "status" => 'sucess',
                "msg" => '接单成功',
                'serial' => $serial
            ];
            return response()->json($data);
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '此游戏已被他人选择，请重新选择'
            ];
            return response()->json($data);
        }
    }

    /**
     * 订单详情
     */
    public function order_info(Request $request){
        $serial = $request->serial;
        if(empty($serial)){
            return response()->json('参数错误');
        }
        $list = DB::table('order_record as o')
            ->leftJoin('buyer as b','o.buyer','=','b.id')
            ->leftJoin('task_record as t','o.task','=','t.id')
            ->select('b.name','o.serial','o.type','o.keywords','t.filter','t.commen_keywords','t.sort_style','t.receive_num')
            ->where('o.serial','=',$serial)
            ->get()
            ->toArray();

        $data = [
            "data"=>$list[0],
        ];

        return response()->json($data);
    }

    /**
     * 取消订单
     */
    public function order_off(Request $request){
        $serial = $request->serial;
        if(empty($serial)){
            $data = [
                "status" => 'fail',
                "msg" => '参数错误'
            ];
            return response()->json($data);
        }
        $update = array(
            'receiving_id'=> '',
            'buyer'  => '',
            'status' => 0
        );

        $result = DB::table('order_record')
            ->where('serial','=',$serial)
            ->update($update);

        if($result){
            $data = [
                "status" => 'success',
                "msg" => '订单已取消'
            ];
            return response()->json($data);
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '取消失败，请重试'
            ];
            return response()->json($data);
        }
    }

    /**
     * 完成订单
     */
    public function order_complete(Request $request){
        $id = $request->user_id;
        $serial = $request->serial;
        $shop_name = $request->shop_name;
        $goods_url = $request->goods_url;
        $keywords = $request->keywords;
        // if(empty($serial) || empty($shop_name) || empty($goods_url) || empty($keywords)){
        //     return response()->json('参数错误');
        // }

        $list = DB::table('order_record')
            ->select('shop_name','goods_url','keywords')
            ->where('serial','=',$serial)
            ->first();

        if($list->shop_name != $shop_name){
            $data = [
                "status" => 'fail',
                "msg" => '店铺名称错误'
            ];
            return response()->json($data);
        }
        if($list->goods_url != $goods_url){
            $data = [
                "status" => 'fail',
                "msg" => '商品链接错误'
            ];
            return response()->json($data);
        }
        if($list->keywords != $keywords){
            $data = [
                "status" => 'fail',
                "msg" => '商品关键字错误'
            ];
            return response()->json($data);
        }

        $result = DB::table('order_record')
            ->where('serial','=',$serial)
            ->update(['status'=>2]);

        if($result) {
            $m = DB::table('brokerage_record')
                ->where('user_id', '=', $id)
                ->orderByDesc('ctime')
                ->select('balance')
                ->first();
            
            if($m){
                $balance = $m->balance;
            }else{
                $balance = 0;
            }

            $balance += 0.5;

            $Getid = DB::table('brokerage_record')->insertGetId(
                [
                    'user_id'=>$id,
                    'type' => '3',
                    'in_out' => '0',
                    'content' => '订单提成',
                    'quota' => 0.5,
                    'balance' => $balance,
                    'ctime' => date('Y-m-d H:i:s',time()),
                ]
            );
    
            if ($Getid){
                $data = [
                    "status" => 'success',
                    "msg" => '订单完成'
                ];
                return response()->json($data);
            }
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '系统繁忙，请重试'
            ];
            return response()->json($data);
        }
    }

    /**
     * 垫付任务完成订单
     */
    public function order_complete_df(Request $request){
        $serial = $request->serial;
        $user_id = $request->id;
        $pic = $request->pic;
        $alipay_order = $request->alipay_order;
        $fee = $request->fee;

        $result = DB::table('order_record')
            ->where('serial','=',$serial)
            ->update(['status'=>3]);

        $Getid = DB::table('complete_record')->insertGetId(
            [
                'user_id' => $user_id,
                'serial'=> $serial,
                'pic' => $pic,
                'alipay_order' => $alipay_order,
                'fee' => $fee,
                'status' => '0',
                'ctime' => date('Y-m-d H:i:s',time()),
            ]
        );

        if ($Getid){
            $data = [
                "status" => 'success',
                "msg" => '已完成,请等待商家审核'
            ];
            return response()->json($data);
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '系统繁忙，请重试'
            ];
            return response()->json($data);
        }
    }

    /**
     * 垫付任务商家审核通过返款
     */
    public function order_df_check(Request $request){
        $serial = $request->serial;

        $list = DB::table('order_record as o')
            ->leftJoin('buyer as b','o.buyer','=','b.id')
            ->leftJoin('complete_record as c','c.serial','=','o.serial')
            ->select('b.name','o.serial','o.ctime','o.charge','o.price','c.status','c.alipay_order')
            ->where('o.serial','=',$serial)
            ->first();

        $data = [
            "data"=>$list,
        ];
        return response()->json($data);
    }

    /**
     * 添加买手
     */

    public function add_buyer(Request $request){
        $user_id = $request->id;
        $name = $request->name;
        $platform = $request->platform;
        $sex = $request->sex;
        $Ymd = $request->Ymd;
        $credit = $request->credit;
        $tag = $request->tag;
        $serial = $request->serial;
        $receiver_name = $request->receiver_name;
        $receiver_tel = $request->receiver_tel;
        $address = $request->address;
        $street = $request->street;

        $Getid = DB::table('buyer')->insertGetId(
            [
                'user_id'=>$user_id,
                'name' => $name,
                'platform' => $platform,
                'sex' => $sex,
                'Ymd' => $Ymd,
                'credit' => $credit,
                'tag' => $tag,
                'serial' => $serial,
                'receiver_name' => $receiver_name,
                'receiver_tel' => $receiver_tel,
                'address' => $address,
                'street' => $street,
                'status' => '0',
                'ctime' => date('Y-m-d H:i:s',time()),
            ]
        );

        if ($Getid){
            $data = [
                "status" => 'success',
                "msg" => '添加买手成功'
            ];
            return response()->json($data);
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '系统繁忙，请重试'
            ];
            return response()->json($data);
        }

    }

    /**
     * 买手列表
     */
    public function buyer_list(Request $request){
        $user_id = $request->id;

        if(empty($user_id)){
            return response()->json('参数错误');
        }

        if($request->has('status')){
            $status = $request->status;
            $builder = DB::table('buyer')
            ->select('id','name','platform','status')
            ->where('user_id','=',$user_id)
            ->where('status','=',$status);
        }else{
            $builder = DB::table('buyer')
            ->select('id','name','platform','status')
            ->where('user_id','=',$user_id);
        }

        $list = $builder->orderBy('ctime', 'desc')->get()->toArray();

        $tb = [];
        $jd = [];
        $pdd = [];
        
        foreach ($list as $l){
            $temp = [
                'id' => $l->id,
                'name' => $l->name,
                'status' => $l->status,
            ];
            if($l->platform == 0){
                array_push($tb,$temp);
            }
            if($l->platform == 1){
                array_push($jd,$temp);
            }
            if($l->platform == 2){
                array_push($pdd,$temp);
            }
        }


        $data = [
            "data" => (object)[
                '0' => $tb,
                '1' => $jd,
                '2' => $pdd,
            ],
        ];
        return response()->json($data);
    }

    /**
     * 买手详情
     */
    public function buyer_info(Request $request){
        $id = $request->id;

        $info = DB::table('buyer')
            ->where('id','=',$id)
            ->first();

        $data = [
            'data'=>$info
        ];
        
        return response()->json($data);  
    }

    /**
     * 修改买手信息
     */
    public function update_buyer(Request $request){
        $id = $request->id;
        $name = $request->name;
        $sex = $request->sex;
        $Ymd = $request->Ymd;
        $serial = $request->serial;
        $receiver_name = $request->receiver_name;
        $receiver_tel = $request->receiver_tel;
        $address = $request->adress;
        $street = $request->street;

        DB::table('buyer')
            ->where('id', '=', $id)
            ->update([
                'name' => $name,
                'sex' => $sex,
                'Ymd' => $Ymd,
                'serial' => $serial,
                'receiver_name' => $receiver_name,
                'receiver_tel' => $receiver_tel,
                'address' => $address,
                'street' => $street
            ]);

            $data = [
                "status" => 'success'
            ];
            return response()->json($data);
        
            // if ($result){
                
            // }else{
            //     $data = [
            //         "status" => 'fail',
            //         "message" => '系统繁忙，请重试'
            //     ];
            //     return response()->json($data);
            // }
    }

    /**
     * 删除买手
     */
    public function delete_buyer(Request $request){
        $id = $request->id;

        $result = DB::table('buyer')
            ->where('id','=',$id)
            ->delete();

        if(!empty($result)){
            $data = [
                "status" => 'success'
            ];
            return response()->json($data);
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '系统繁忙，请重试'
            ];
            return response()->json($data);
        }
    }


    /**
     * 任务列表
     */
    public function task_list(Request $request){
        $platform = $request->input('platform');

        $builder = DB::table('task_record as t')
            ->leftJoin('shop as s','t.shop_id','=','s.id')
            ->select('t.task_type','t.task_name','t.goods_name','t.goods_url','t.goods_key','t.goods_pic','t.goods_price','t.goods_num','s.store_name','s.wangwang','s.url')
            ->where('t.platform','=',$platform);

        $list = $builder->orderBy('t.ctime', 'desc')->get()->toArray();

        $data = [
            "data"=>$list,
        ];
        return response()->json($data);
    }

    /**
     * 查询订单状态
     */
    public function order_has(Request $request){
        $user_id = $request->id;
        $status = $request->status;
        $wrap_type = $request->wrap_type;

        if(empty($user_id) || empty($status)){
            return response()->json('参数错误');
        }

        $builder = DB::table('order_record')
            ->where('receiving_id','=',$user_id)
            ->where('status','=',$status)
            ->where('wrap_type','=',$wrap_type);

        $list = $builder->orderBy('ctime', 'desc')->get()->toArray();

        $data = [
            "data"=>$list,
        ];
        return response()->json($data);
    }

    /**
     * 实名认证信息
     */

     public function certification_list(Request $request){
        $user_id = $request->id;

        if(empty($user_id)){
            return response()->json('参数错误');
        }

        $builder = DB::table('certification')
        ->where('user_id','=',$user_id);

        $list = $builder->first();

        if($list){
            $data = [
                "data" => $list
            ];
        }else{
            $data = [
                "data"=> (object)[
                    'status' => 0
                ],
            ];
        }

        return response()->json($data);
        
     }

     /**
      * 添加实名认证
      */
    public function add_certification(Request $request){
        $user_id = $request->id;
        $name = $request->name;
        $card = $request->card;
        $pic_front = $request->pic_front;
        $pic_back = $request->pic_back;

        $Getid = DB::table('certification')->insertGetId(
            [
                'user_id'=>$user_id,
                'name' => $name,
                'card' => $card,
                'pic_front' => $pic_front,
                'pic_back' => $pic_back,
                'status' => '1',
                'ctime' => date('Y-m-d H:i:s',time()),
            ]
        );

        if ($Getid){
            $data = [
                "status" => 'success'
            ];
            return response()->json($data);
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '系统繁忙，请重试'
            ];
            return response()->json($data);
        }
    }

    /**
     * 银行列表
     */

    public function bank_list(Request $request){
        $user_id = $request->id;

        // if(empty($user_id)){
        //     return response()->json('参数错误');
        // }

        $builder = DB::table('bank')
        ->where('user_id','=',$user_id);

        if($request->has('status')){
            $status = $request->status;

            $builder = $builder->where('status','=',$status);

        }

    
        $list = $builder->orderBy('ctime', 'desc')->get()->toArray();

        $data = [
            "data"=>$list,
        ];

        return response()->json($data);
    }

    /**
     * 添加银行
     */
    public function add_bank(Request $request){
        $user_id = $request->id;
        $name = $request->name;
        $card = $request->card;
        $deposit = $request->deposit;
        $pic_bank = $request->pic_bank;

        $Getid = DB::table('bank')->insertGetId(
            [
                'user_id'=>$user_id,
                'name' => $name,
                'card' => $card,
                'deposit' => $deposit,
                'pic_bank' => $pic_bank,
                'status' => '0',
                'ctime' => date('Y-m-d H:i:s',time()),
            ]
        );

        if ($Getid){
            $data = [
                "status" => 'success'
            ];
            return response()->json($data);
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '系统繁忙，请重试'
            ];
            return response()->json($data);
        }
    }

    /**
     * 用户详情接口
     */
    public function user_info(Request $request){
        $id = $request->id;

        // $sub = DB::table('brokerage_record')
        //     ->select(['aid'])
        //     ->selectRaw('max(id) as id')
        //     ->grouBy('id');

        // $_list = DB::table('a')->leftJoin(DB::raw('({$sub->toSql()}) as b),'a.id','=','b.aid)->get()

        $result = DB::table('brokerage_record')
            ->where('user_id','=',$id)
            ->select('balance')
            ->orderBy('ctime','desc')
            ->first();

        if($result){
            $data = [
                "data"=>$result,
            ];
        }else{
            $data = [
                "data"=>['balance'=>'0'],
            ];
        }

        return response()->json($data);
    }

    /**
     * 本金提现
     */
    public function add_advance(Request $request){
        $user_id = $request->user_id;
        $bank_id = $request->bank_id;
        $money = $request->balance;
        $serial = date('YmdHis') . $user_id;

        $m = DB::table('brokerage_record')
            ->where('user_id', '=', $user_id)
            ->orderByDesc('ctime')
            ->select('balance')
            ->first();

        if ($m) {
            $balance = $m->balance;
        } else {
            $balance = 0;
        }

        if ($balance < $money) {
            $data = [
                "status" => 'fail',
                "msg" => '提现金额大于余额，请重新再试!'
            ];
            return response()->json($data);
        }

        $balance = $balance - $money;

        DB::table('brokerage_record')->insert(
            [
                'user_id' => $user_id,
                'type' => '4',
                'in_out' => '1',
                'content' => '提现',
                'quota' => $money,
                'balance' => $balance,
                'ctime' => date('Y-m-d H:i:s', time()),
            ]
        );

        $Getid = DB::table('advance_record')->insertGetId(
            [
                'user_id'=>$user_id,
                'bank_id' => $bank_id,
                'balance' => $money,
                'serial' => $serial,
                'status' => '0',
                'ctime' => date('Y-m-d H:i:s',time()),
            ]
        );

        if ($Getid){
            $data = [
                "status" => 'success'
            ];
            return response()->json($data);
        }else{
            $data = [
                "status" => 'fail',
                "msg" => '系统繁忙，请重试'
            ];
            return response()->json($data);
        }
    }

    /**
     * 提现记录
     */
    public function advance_list(Request $request){
        $id = $request->id;

        $result = DB::table('advance_record as a')
            ->leftJoin('users as u','a.user_id','=','u.id')
            ->leftJoin('bank as b','a.bank_id','=','b.id')
            ->select('u.name','b.card','a.balance','a.serial','a.status','a.desc','a.ctime')
            ->where('a.user_id','=',$id)
            ->orderBy('a.ctime','desc')
            ->get()
            ->toArray();

        $data = [
            "data"=>$result,
        ];

        return response()->json($data);

    }
}