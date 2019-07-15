define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cash/log/index',
                    add_url: 'cash/log/add',
                    edit_url: 'cash/log/edit',
                    del_url: 'cash/log/del',
                    multi_url: 'cash/log/multi',
                    table: 'cash_log',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title:"id",operate:false},
                        {field: 'user.nickname', title: __('User_id'),operate:false},
                      /*  {field: 'reCompany.name', title: __('Re_company_id'),operate:false},*/
                        /*{field: 'way', title: __('Way')}, */
                        {field: 'ch_way', title: "变动方向",operate:false},
                      /*  {field: 'tip', title: __('Tip'),operate:false},*/
                      /*  {field: 'rec_id', title: __('Rec_id')},*/
                        {field: 'cash', title: __('Cash'), operate:'BETWEEN'},
                      /*  {field: 're_compaccountdetail_id', title: __('Re_compaccountdetail_id')},
                        {field: 'with_id', title: __('With_id')},
                        {field: 'order_no', title: __('Order_no')},*/
                        {field: 'type', title: "变动类别",visible:false, searchList: {'1':"用户购买金币", '2': "用户充值月度会员",'3':'用户充值年度会员'}},
                        {field: 'ch_type', title:"变动类别",operate:false},
                     /*   {field: 'status', title: __('Status')},*/
                      /*  {field: 'reApplyCompany.name', title: "入职公司"},
                        {field: 'applyUser.nickname', title: "相关用户"},*/
                       /* {field: 'training.name', title:"参与活动"},*/
                        {field: 'update_at', title: "操作时间", operate:'RANGE', addclass:'datetimerange'},
                     /*   {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}*/
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});