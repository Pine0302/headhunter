define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/salarymodel/index',
                    add_url: 're/salarymodel/add',
                  //  edit_url: 're/salarymodel/edit',
                    del_url: 're/salarymodel/soft_del',
                    multi_url: 're/salarymodel/multi',
                    pass_url: 're/salarymodel/pass',
                    export_url: 're/salarymodel/export',
                 //   import_url: 're/salarymodel/import',
                    table: 're_salarymodel',
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
                        {field: 'reCompany.name', title: '公司名称'},
                        {field: 'name', title: __('Name')},
                      /*  {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},*/
                        {field: 'update_at', title: '修改时间', operate:'RANGE', addclass:'datetimerange'},
                       /* {field: 'admin_id', title: __('Admin_id')},*/
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter:  function (value, row, index) {
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
                            if (options.extend.pass_url !== '') {
                              /*  buttons.push({
                                    name: 'pass',
                                    icon: 'fa fa-check',
                                    title:'模板',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-passone',
                                });*/
                            }

                            if (options.extend.export_url !== '') {
                                buttons.push({
                                    name: 'pass',
                                    icon: 'fa fa-check',
                                    url:options.extend.export_url,
                                    title:'模板导出',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary',
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
                            return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                        }}
                    ]
                ],
            });

      //      table.bootstrapTable('refresh', {});
          //  Toastr.info("当前执行的是自定义搜索");
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