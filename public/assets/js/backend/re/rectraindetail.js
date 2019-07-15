define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/rectraindetail/index',
                    add_url: 're/rectraindetail/add',
                    edit_url: 're/rectraindetail/edit',
                    del_url: 're/rectraindetail/del',
                    multi_url: 're/rectraindetail/multi',
                    table: 're_rectraindetail',
                    pass_url: 're/rectraindetail/pass',
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
                      /*  {field: 'id', title: __('Id')},*/
                        {field: 'reCompany.name', title: __('Re_company_id')},
                      /*  {field: 're_trainorder_id', title: __('Re_trainorder_id')},*/
                        {field: 'user.nickname', title: __('Low_user_id')},
                        {field: 'recUser.nickname', title: __('Up_user_id')},
                      /*  {field: 'rec_user_id', title: __('Rec_user_id')},*/
                        {field: 'up_cash', title: __('Up_cash'), operate:'BETWEEN'},
                      /*  {field: 'p_cash', title: __('P_cash'), operate:'BETWEEN'},*/
                        {field: 'reward_type', title: "平台抽佣方式"},
                        /*{field: 'status', title: __('Status')},*/
                        {field: 'status',  title: '状态', searchList: {'1':"已发奖", '2': "未发奖"}, style: 'min-width:100px;'},
                        {field: 'total_cash', title: __('Total_cash'), operate:'BETWEEN'},
                     /*   {field: 'admin_id', title: __('Admin_id')},*/
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                     /*   {field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},*/
                        {field: 'reTraining.name', title: __('Re_training_id')},
                        {field: 'reason', title: __('Reason')},
                        /*{field: 'up_company_id', title: __('Up_company_id')},*/
                        {field: 'timeline', title: __('Timeline'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'deadline', title: __('Deadline'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组
                            var buttons = $.extend([], this.buttons || []);
                            if(row.status_ori==2){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'发送奖励',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                    });
                                }
                                /*  if (options.extend.edit_url !== '') {
                                 buttons.push({
                                 name: 'edit',
                                 icon: 'fa fa-circle',
                                 title:'取消发奖',
                                 extend: 'data-toggle="tooltip"',
                                 classname: 'btn btn-xs btn-primary btn-editone',
                                 url: options.extend.edit_url
                                 });
                                 }*/

                                if (options.extend.edit_url !== '') {
                                    buttons.push({
                                        name: 'edit',
                                        icon: 'fa fa-exchange',
                                        title: '编辑状态',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-editone',
                                        // url: options.extend.edit_url
                                    });
                                }

                            }
                            return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                        }}
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