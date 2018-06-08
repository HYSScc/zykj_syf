<?php

namespace App\Admin\Controllers;

use App\Models\Exchange;
use App\Models\User;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class CashsController extends Controller
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

            $content->header('消费明细');
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

            $content->header('消费明细');
            $content->description('查看');

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

            $content->header('消费明细');
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
        return Admin::grid(Exchange::class, function (Grid $grid) {
            $grid->model()->where('status', Exchange::CASH_STATUS)->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('users.username', '用户');
            $grid->total('总额');
            $grid->column('amount', '消费')->display(function () {
              return $this->type == 1 ? '<i style="color:#3c8dbc;">+</i> '. $this->amount : '<i style="color:#dd4b39;">-</i> ' . $this->amount;
            });
            $grid->current('结束');
            $grid->created_at('添加时间');
            // $grid->updated_at();
            $grid->disableExport();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->filter(function ($filter) {
              $filter->disableIdFilter();
              $filter->equal('user_id', '用户')->select(User::all()->pluck('username', 'id'));
              $filter->between('created_at', '添加时间')->datetime();
            });
            $grid->disableActions();
            // $grid->actions(function ($actions) {
            //   $actions->disableDelete();
            // });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Exchange::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->select('user_id', '用户')->options(User::all()->pluck('username', 'id'));
            $form->currency('total', '总额');
            $form->currency('amount', '消费');
            // $form->currency('current', '结束');
            $form->radio('model', '来源')->options(['1'=> 'Exchanges','2'=> 'Order'])->default('1');
            $form->number('uri', '标注');
            // $form->saving(function (Form $form) {
            //   $form->uri =
            // });
            $form->display('created_at', '添加时间');
            $form->display('updated_at', '编辑时间');
        });
    }
}
