define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/companyapply/index',
                    add_url: 're/companyapply/add',
                   // edit_url: 're/companyapply/edit',
                //    deny_url: 're/companyapply/deny',
                    pass_url: 're/companyapply/pass',
                    del_url: 're/companyapply/del',
                    multi_url: 're/companyapply/multi',
                    table: 're_company_apply',
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
                        {field: 'id', title: __('Id')},
                        {field: 'hr.nickname', title: '用户昵称'},
                        {field: 'hr.mobile', title: '手机号'},
                        {field: 'company.name', title:"公司名称"},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"1":__('Status 1')}},
                        {field: 'status_text', title: __('Status'), operate:false},
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                        var table = this.table;
                        // 操作配置
                        var options = table ? table.bootstrapTable('getOptions') : {};
                        // 默认按钮组
                        var buttons = $.extend([], this.buttons || []);
                        if (options.extend.edit_url !== '') {
                            buttons.push({
                                name: 'edit',
                                icon: 'fa fa-exchange',
                                title: '编辑状态',
                                extend: 'data-toggle="tooltip"',
                                classname: 'btn btn-xs btn-primary btn-editone',
                                // url: options.extend.detail_url
                            });
                        }

                        /*if (options.extend.detail_url !== '') {
                         buttons.push({
                         name: 'detail',
                         icon: 'fa fa-list',
                         title: '详情',
                         extend: 'data-toggle="tooltip"',
                         classname: 'btn btn-xs btn-primary btn-detailone',
                         // url: options.extend.detail_url
                         });
                         }*/

                        if (options.extend.del_url !== '') {
                            buttons.push({
                                name: 'del',
                                icon: 'fa fa-trash',
                                title: __('Del'),
                                extend: 'data-toggle="tooltip"',
                                classname: 'btn btn-xs btn-danger btn-delone'
                            });
                        }

                        if(row.status==0){
                            if (options.extend.pass_url !== '') {
                                buttons.push({
                                    name: 'pass',
                                    icon: 'fa fa-check',
                                    title:'通过',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-passone',
                                    //   url: options.extend.detail_url
                                });
                            }


                            if (options.extend.deny_url !== '') {
                                buttons.push({
                                    name: 'deny',
                                    icon: 'fa fa-ban',
                                    title:'拒绝',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-denyone',
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