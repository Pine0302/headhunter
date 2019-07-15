define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/usercomp/index',
                    add_url: 're/usercomp/add',
                    edit_url: 're/usercomp/edit',
                    del_url: 're/usercomp/del',
                    multi_url: 're/usercomp/multi',
                    pass_url: 're/usercomp/pass',
                    deny_url: 're/usercomp/deny',
                    table: 're_usercomp',
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
                        {field: 'id', title: __('Id'),operate:false,},
                        {field: 'user.username', title: "姓名",operate:false,},
                        {field: 'user.mobile', title: "电话",operate:false,},
                        {field: 'reCompany.name', title: __('Re_company_id'), operate:false},
                        {field: 're_company_id', title: __('Re_company_id'), visible:false, searchList: $.getJSON("re/usercomp/searchComp")},
                    /*    {field: 'reJob.name', title: __('Re_job_id'),operate:false,},*/
                       /* {field: 're_apply_id', title: __('Re_apply_id')},*/
                        {field: 'status', title: __('Status'),visible:false,searchList: {'1':"在职", '2': "已离职"},},
                        {field: 'ch_status', title: "是否在职",operate:false},
                   /*     {field: 'admin_id', title: __('Admin_id')},*/
                        {field: 'create_at', title: __('Create_at'), operate:false, addclass:'datetimerange'},
                        {field: 'update_at', title: __('Update_at'), operate:false, addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组
                            var buttons = $.extend([], this.buttons || []);
                            //  options.exportDataType = "selected";
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

                            if (options.extend.detail_url !== '') {
                                buttons.push({
                                    name: 'detail',
                                    icon: 'fa fa-list',
                                    title: '详情',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-detailone',
                                    // url: options.extend.detail_url
                                });
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
                            if(row.status==1){
                                if (options.extend.deny_url !== '') {
                                    buttons.push({
                                        name: 'deny',
                                        icon: 'fa fa-times',
                                        title:'离职',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-denyone',
                                    });
                                }
                            }

                            return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                        }}
                    ]
                ],
                showExport: false,
                searchFormVisible: true,
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