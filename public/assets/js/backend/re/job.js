define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/job/index',
                    add_url: 're/job/add',
                    edit_url: 're/job/edit',
                    pass_url:'re/job/pass',
                    deny_url:'re/job/deny',
                    online_url:'re/job/online',
                    offline_url:'re/job/offline',
                    detail_url:'re/job/detail',
                    del_url: 're/job/del',
                    multi_url: 're/job/multi',
                    table: 're_job',
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
                        {field: 'reCompany.name', title:'公司名称'},
                        {field: 'name', title: __('Name')},
                      //  {field: 'num', title:'招聘人数'},
                        {field: 'area', title: __('Area'),operate:false},
                        {field: 'is_hot', title: '是否推荐',operate:false},
                        {field: 'address', title: __('Address'),operate:false},
                       /* {field: 'mini_age', title: __('Mini_age')},
                        {field: 'max_age', title: __('Max_age')},*/
                        {field: 'mini_salary', title: __('Mini_salary'), operate:'BETWEEN'},
                        {field: 'max_salary', title: __('Max_salary'), operate:'BETWEEN'},
                        {field: 'reward', title: __('Reward'), operate:'BETWEEN'},
                  /*      {field: 'reward_type',  title: '推荐获佣金方式', searchList: {'1':"固定金额", '2': "固定比例"}, style: 'min-width:100px;'},
                        {field: 'reward_days', title: "奖励金周期(日)"},*/
                      /*  {field: 'hits', title: __('Hits')},*/
                     /*   {field: 'coordinate', title:"经纬度"},*/
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
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
                        if(row.is_admin==1){
                            if(row.is_hot=="未推荐"){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'设为推荐',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                        //   url: options.extend.detail_url
                                    });
                                }
                            }
                            if(row.is_hot=="推荐"){
                                if (options.extend.deny_url !== '') {
                                    buttons.push({
                                        name: 'deny',
                                        icon: 'fa fa-times',
                                        title:'取消推荐',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-denyone',
                                    });
                                }
                            }
                        }
                        if(row.status==2){
                            if (options.extend.online_url !== '') {
                                buttons.push({
                                    name: 'pass',
                                    icon: 'fa fa-circle',
                                    title:'设为上线',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-onlineone',
                                    //   url: options.extend.detail_url
                                });
                            }
                        }
                        if(row.status==1){
                            if (options.extend.offline_url !== '') {
                                buttons.push({
                                    name: 'deny',
                                    icon: 'fa fa-ban',
                                    title:'设为下线',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-offlineone',
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