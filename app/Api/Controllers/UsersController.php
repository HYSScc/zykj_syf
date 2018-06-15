<?php

namespace App\Api\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Recommend;
use App\Models\UserCoupon;
use Carbon\Carbon;
use App\Models\OrderItem;
use App\Models\Exchange;
use Illuminate\Http\Request;
use App\Api\Requests\UserRequest;
use App\Repositories\ExchangeRepositoryEloquent;
# 个人中心
class UsersController extends Controller
{
    public $exchanges;

    public function __construct(ExchangeRepositoryEloquent $exRepository)
    {
        $this->exchanges = $exRepository;
    }
    /**
    * 首页
    */
    public function index()
    {

    }

    //我的代理，我的M币 我的业绩，我的客户，我的收入，我的粉丝，
    /**
    * 我的客户--分享赚
    */
    public function client()
    {
        $data = Exchange::where(['user_id' => auth()->user()->id, 'status' => Exchange::AWARD_STATUS])->orderBy('created_at', 'desc')->get();
        $datas = [];
        foreach($data as $key => $item) {
          // dd($item->uri);
            $data = $this->get_order_item($item->uri);
            $data->amount = $item->amount;
            $data->time = $item->created_at;
            $datas[] = $data;
        }
        return response()->json(['data' => $datas]);
    }

    public function get_order_item($id)
    {
        $data = OrderItem::find($id);
        $user = $data->users;
        $info = (object)[];
        // dd($user->username);
        $info->user_name = $user->username ? $user->username : $user->nickname;
        $info->user_image = $user->avatar;
        $info->title = $data->title;
        $info->total = $data->total_price;
        return $info;
    }
    /**
    * 我的收入--分享赚
    */
    public function earn()
    {
        $user = auth()->user();
        $data = Exchange::where(['user_id' => auth()->user()->id, 'status' => Exchange::AWARD_STATUS])->orderBy('crated_at, desc')->get();
        $datas = [];
        foreach($data as $key => $item) {
            $data = $this->get_order_item($item->uri);
            $data->amount = $item->amount;
            $data->time = $item->created_at;
            $datas[] = $data;
        }

        return response()->json(['status' => 'success', 'code' => '201', 'data' => $datas, 'user' => '']);
    }

    public function clearSpend($userID, $data)
    {
        $user = User::find($userID);

        if ($data['type'] == 0) {
          $start = Carbon::parse($data['date']->startOfDay());
          $end = Carbon::parse($data['date']->endOfDay());
        } else {
          $start = Carbon::parse($data['date']->startOfMonth());
          $end = Carbon::parse($data['date']->endOfMonth());
        }
        
        $where[] = ['created_at', '>=', $start];
        $where[] = ['created_at', '<=', $end];
        $where[] = ['status', Exchange::CASH_STATUS];
        $datas = Exchange::select('amount')->where($where)->get();
        $total = $datas ? collect($datas)->sum('amount') : 0;
        return $total;
    }
    /**
    * 我的粉丝
    */
    public function history()
    {
        $arr = ['type' => 0, 'date' => Carbon::now()];
        return $spend = $this->clearSpend(auth()->id(), $arr);
        dd($spend);
        $datas = \Redis::zrevrange('history.' . auth()->id() . '.chilren', 0, -1);
        $logs = [];
        $count = 0;

        if ($datas) {
          $logs = collect($datas)->map(function ($item, $index) {
            $arr = explode('_', $item);
            $user = User::find($arr[0]);
            $data['name'] = $user->username ? $user->username : $user->nickname;
            $data['avatar'] = $user->avatar;
            $data['id'] = $arr[0];
            $data['pro_id'] = $arr[1];
            $data['pro_title'] = $arr[2];
            $data['time'] = Carbon::createFromTimestamp($arr[3])->toDateTimeString();
            return $data;
          });
          $ids = collect($logs)->pluck('id');
          $count = collect($ids)->unique()->count();
        }

        return response()->json(['status' => 'success', 'code' => '201', 'data' => $count, 'logs' => $logs]);
    }

    /**
    * 我的代理
    */
    public function agent()
    {
        $user = auth()->user();
        $datas = $this->exchanges->begats($user->id);
        if ($user->status != 2) {
          return response()->json(['status' => 'fail', 'code' => '401', 'message' => '您还不没有升级哦']);
        }

        $result = Recommend::where('user_id', $user->id)->first();

        if (!$result) {
          return response()->json(['status' => 'fail', 'code' => '422', 'message' => '系统出现错误']);
        }
        $res = $this->tree($result->member);
        $other = count($res) ? collect($res)->sum('recommend') : 0;
        $data = [
          'id' => $user->id,
          'recommend' => $result->recommend,
          'other' => $other,
          'sum' => $result->recommend + $other,
          'team' => $res
        ];
        return response()->json(['data' => $data, 'status' => 'success', 'code' => '201']);
    }

    public function tree($ids)
    {
        $result = [];
        if ($ids = json_decode($ids)) {
          $data = Recommend::whereIn('user_id', $ids)->get();
          if($data) {
            $result = collect($data)->map(function ($item, $key) {
              return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'user_avatar' => $item->user->avatar,
                'user_name' => $item->user->username,
                'recommend' => $item->recommend
              ];
            });
          }
        }
        return $result;
    }
    /**
    * 我的优惠券
    */
    public function coupons()
    {
      $user = auth()->user();
      $data = UserCoupon::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

      $datas = collect($data)->map(function ($item, $key) {
        $info = $item->coupons;

        $start_at = Carbon::parse($info->start_at)->toDateString();
        $end_at = Carbon::parse($info->end_at)->toDateString();
        $end = Carbon::parse($info->end_at);
        $now = Carbon::now();

        if ($item->status == 0 && $now->gt($end)) {
            $item->status = 2;
            $item->save();
        }
        $status = $item->status;
        return [
          'id' => $item->id,
          'user_id' => $item->user_id,
          'coupon_id' => $item->coupon_id,
          'coupon_par' => $info->par_value,
          'coupon_more' => $info->more_value,
          'coupon_active' => $start_at. '/' .$end_at,
          'status' => $info->status == 1 ? $status : 2
        ];
      });
      return response()->json(['status' => 'success', 'code' => '201', 'data' => $datas]);
    }
}
