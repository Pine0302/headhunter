define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/salarygrant/index',
                    add_url: 're/salarygrant/add',
                    edit_url: 're/salarygrant/edit',
                    import_url: 're/salarygrant/import',
                    del_url: 're/salarygrant/del',
                    pass_url: 're/salarygrant/pass',
                    multi_url: 're/salarygrant/multi',
                    table: 're_salarygrant',
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
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'user_id', title: __('User_id'),operate:false},
                        {field: 'user_name', title: __('User_name')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'company_name', title: __('Company_name'),operate:false},
                        {field: 're_company_id', title: "选择公司", visible:false, searchList: $.getJSON("re/usercomp/searchComp")},
                        {field: 'salary', title: __('Salary'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"1":"已发放","2":"未发放"}},
                        {field: 'status_text', title: __('Status'), operate:false},
                        {field: 'tip', title: __('Tip'),operate:false},
                       /* {field: 'admin_id', title: __('Admin_id')},*/
                        {field: 'finish_at', title: __('Finish_at'), operate:'RANGE', addclass:'datetimerange',operate:false},
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange',operate:false},
                    /*    {field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},*/
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,formatter: function (value, row, index) {
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

                            if(row.status==2){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-list',
                                        title: '发放',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                        // url: options.extend.detail_url
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