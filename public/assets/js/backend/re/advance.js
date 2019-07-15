define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/advance/index',
             //       add_url: 're/advance/add',
               //     edit_url: 're/advance/edit',
                    del_url: 're/advance/del',
                    multi_url: 're/advance/multi',
                    pass_url: 're/advance/pass',
                    deny_url: 're/advance/deny',
                    detail_url: 're/advance/detail',
                    table: 're_advance',
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
                     /*   {field: 'id', title: __('Id')},*/
                        {field: 'user.username', title: __('User_id')},
                        {field: 'reCompany.name', title: __('Re_company_id')},
                        {field: 'status',  title: '状态', searchList: {'0':"提出申请", '1': "同意",'2':'拒绝'}, style: 'min-width:100px;'},
                        {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                        {field: 'reResume.id_num', title:"身份证号", operate:false},
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter:  function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组

                            var buttons = $.extend([], this.buttons || []);

                            if (options.extend.detail_url !== '') {
                                buttons.push({
                                    name: 'detail',
                                    icon: 'fa fa-list',
                                    title: '详情',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-detailone'
                                //    url: options.extend.detail_url
                                });
                            }

                            if(row.showtype==0){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'通过申请',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                    });
                                }
                                if (options.extend.deny_url !== '') {
                                    buttons.push({
                                        name: 'deny',
                                        icon: 'fa fa-arrows',
                                        title:'拒绝申请',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-danger btn-denyone',
                                    });
                                }
                            }else{
                                if (options.extend.dragsort_url !== '') {
                                    buttons.push({
                                        name: 'dragsort',
                                        icon: 'fa fa-arrows',
                                        title: __('Drag to sort'),
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-dragsort'
                                    });
                                }
                                if (options.extend.edit_url !== '') {
                                    buttons.push({
                                        name: 'detail',
                                        icon: 'fa fa-list',
                                        title: '详情',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-dialog',
                                        url: options.extend.detail_url
                                    });
                                }
                            }
                            if (options.extend.del_url !== '') {
                                buttons.push({
                                    name: 'del',
                                    icon: 'fa fa-trash',
                                    title: __('Del'),
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-danger btn-delone'
                                });
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