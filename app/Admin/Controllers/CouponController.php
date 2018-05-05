<?php

namespace App\Admin\Controllers;

use App\Models\Coupon;

use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class CouponController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('优惠券');
            $content->description('列表');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('优惠券');
            $content->description('编辑');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('优惠券');
            $content->description('添加');

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Coupon::class, function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->title('说明')->label('success');
            $grid->par_value('优惠券');
            $grid->more_value('满减');
            $grid->quantum('数量');
            $grid->receive('已领取');
            $grid->column('有效期')->display(function(){
              return Carbon::parse($this->start_at)->toDateString().' - '.Carbon::parse($this->end_at)->toDateString();
            });
            $states = [
              'on'  => ['value' => 1, 'text' => '显示', 'color' => 'primary'],
              'off' => ['value' => 0, 'text' => '隐藏', 'color' => 'danger'],
            ];
            $grid->status('开启？')->switch($states);
            // $grid->created_at('创建时间');
            // $grid->updated_at('编辑时间');
            #todo 优惠券领取详情
            $grid->disableExport();
            $grid->filter(function ($filter){
                $filter->disableIdFilter();
                $filter->like('title', '说明');
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Coupon::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('title', '说明');
            $form->currency('par_value', '优惠券')->symbol('￥');
            $form->currency('more_value', '满减')->symbol('￥');
            $form->number('quantum', '数量')->rules('regex:/^[0-9]*$/', [
                'regex' => '数量必须为正整数',
            ])->default(0);
            $form->dateRange('start_at', 'end_at', '有效期');
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '编辑时间');
            //保存前回调
            $form->saving(function (Form $form) {
                #将有效期做修改
                $form->start_at = Carbon::parse($form->start_at)->startOfDay();
                $form->end_at = Carbon::parse($form->end_at)->endOfDay();
                // dd($form->start_at, $form->end_at);
            });
        });
    }
}
