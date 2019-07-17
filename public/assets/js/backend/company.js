define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'company/index',
                    add_url: 'company/add',
                    edit_url: 'company/edit',
                    //del_url: 'company/del',
                  //  multi_url: 'company/multi',
                    detail_url: 'company/detail',
                    pass_url: 'company/pass',
                    deny_url: 'company/deny',
                    table: 're_company',
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
                    /*    {field: 'id', title: __('Id')},*/
                        {field: 'name', title: __('Name')},
                        {field: 're_line.name', title: "所属行业1", operate:false},
                        {field: 'user.username', title:'hr姓名'},
                        {field: 'user.mobile', title:'联系方式'},
                        {field: 'areas', title: '所在城市',operate:false},
                        {field: 'financing',  title: '融资情况', searchList: {'1':"未融资", '2': "天使轮",'3':"A轮", '4': "B轮",'5':"C轮", '6': "D轮及以上",'7':"上市公司", '8': "不需要融资"}, style: 'min-width:100px;'},
                        {field: 'scale',  title: '企业规模', searchList: {'1':"少于15人", '2': "15-50人",'3':"50-150人", '4': "150-500人",'5':"500-2000人", '6': "2000人以上"}, style: 'min-width:100px;'},
                        /*{field: 'address', title:'具体地址'},*/
                      /*  {field: 'status', title: '公司状态'},*/
                  /*      {field: 'yfee', title: '年费', operate:'BETWEEN'},*/
                     /*   {field: 'account', title: __('Account'), operate:'BETWEEN'},*/
                   /*     {field: 'levle', title: '公司类型'},*/


                        {
                            field: 'upload',
                            title: __('简历情况'),
                            table: table,
                            operate:false,
                            formatter: function (value, row, index) {
                                var that = this;
                                var apply_count = row.info.apply_count;
                                var people_count = row.info.people_count;
                                var company_count = row.info.company_count;
                                var company_id = row.info.id;
                                    that = $.extend({}, this);
                                   // var url1 = '/admin/re/apply/index?sort=id&order=desc&offset=0&limit=10&filter='+encodeURIComponent('{"re_company_id":"'+"{ids}"+'"}')+'&op='+encodeURIComponent('{"re_company_id":"="}');
                                  //  var url11 = encodeURIComponent(url1);
                                if((row.status==2)||(row.status==3)){
                                    that.buttons = [
                                        {
                                            name: 'apply',
                                            url: 'company/apply_detail?re_company_id={ids}',
                                            text: '简历数量:' + apply_count,
                                            extend: 'data-toggle="tooltip" data-placement="bottom"',
                                            title: '',
                                            icon: 'fa fa-upload',
                                            classname: 'btn btn-xs btn-info btn-dialog'
                                        },
                                        {
                                            name: 'apply',
                                            url: 'company/pass?re_company_id={ids}',
                                            text: '认证',
                                            extend: 'data-toggle="tooltip" data-placement="bottom"',
                                            title: '',
                                            icon: 'fa fa-upload',
                                            classname: 'btn btn-xs btn-info btn-dialog'
                                        },
                                        {
                                            name: 'apply',
                                            url: 'company/deny?re_company_id={ids}',
                                            text: '拒绝认证',
                                            extend: 'data-toggle="tooltip" data-placement="bottom"',
                                            title: '',
                                            icon: 'fa fa-upload',
                                            classname: 'btn btn-xs btn-info btn-dialog'
                                        },
                                    ];
                                }else{
                                    that.buttons = [
                                        {
                                            name: 'apply',
                                            url: 'company/apply_detail?re_company_id={ids}',
                                            text: '简历数量:' + apply_count,
                                            extend: 'data-toggle="tooltip" data-placement="bottom"',
                                            title: '',
                                            icon: 'fa fa-upload',
                                            classname: 'btn btn-xs btn-info btn-dialog'
                                        },
                                        {
                                            name: 'apply',
                                            url: 'company/deny?re_company_id={ids}',
                                            text: '拒绝认证',
                                            extend: 'data-toggle="tooltip" data-placement="bottom"',
                                            title: '',
                                            icon: 'fa fa-upload',
                                            classname: 'btn btn-xs btn-info btn-dialog'
                                        },
                                    ];
                                }



                                return Table.api.formatter.buttons.apply(that, [value, row, index]);
                            },
                         /*   buttons: [
                                {
                                    name: 'upload',
                                    url: 'pay/qrcode/add?product_id={ids}',
                                    text: '上传价格二维码',
                                    extend: 'data-toggle="tooltip" data-placement="left"',
                                    title: '请上传固定金额收款二维码',
                                    icon: 'fa fa-upload',
                                    classname: 'btn btn-xs btn-primary btn-dialog'
                                },
                                {
                                    name: 'view',
                                    url: 'pay/qrcode/index?product_id={ids}',
                                    text: '查看二维码',
                                    icon: 'fa fa-eye',
                                    classname: 'btn btn-xs btn-warning btn-dialog'
                                }
                            ]*/
                        },




                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter:  function (value, row, index) {
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
                                    //   url: options.extend.detail_url
                                });

                            }
                            /*if (options.extend.detail_url !== '') {
                                buttons.push({
                                    name: 'detail',
                                    icon: 'fa fa-exchange',
                                    title:'导出小程序码',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-detailone',

                                });
                            }*/



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
        apply_detail: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'company/apply_detail',
                 //   add_url: 're/apply/add',
                   // edit_url: 're/apply/edit',
                    //del_url: 're/apply/del',
                    //pass_url:'re/apply/pass',
                    //deny_url:'re/apply/deny',
                    //detail_url:'re/apply/detail',
                    //multi_url: 're/apply/multi',
                    //ajax_url: 're/apply/test',
                    table: 're_apply',
                    //     dragsort_url: 're/apply/test',
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
                        {field: 'reResume.name', title: '用户姓名'},
                        {field: 'reResume.age', title: '出生年月'},
                        {field: 'reResume.gender', title: '性别'},
                        {
                            field: 'button',
                            width: "120px",
                            title: __('按钮组'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'ajax',
                                    url: 're/apply/test',
                                    text:'点击查看手机号',
                                    title:"点击查看手机号",
                                    classname: 'btn btn-block btn-default btn-ajax',
                                    confirm:'确定查看?',
                                    refresh:true,
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                        //    Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;

                                        // table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        /*console.log(data, ret);
                                         Layer.alert(ret.msg);
                                         return false;*/
                                        $(".btn-refresh").trigger("click");
                                    }
                                },
                            ],
                            formatter: function (value, row, index) {
                                if(row.offer_status!=0) {
                                    return row.reResume.mobile
                                } else{
                                    var that = $.extend({},this);
                                    $.extend(that,{
                                        buttons:[{
                                            name: 'ajax',
                                            url: 're/apply/test',
                                            text:'点击查看手机号',
                                            title:"点击查看手机号",
                                            classname: 'btn btn-block btn-default btn-ajax',
                                            confirm:'确定查看?',
                                            refresh:true,
                                            success: function (data, ret) {
                                                //Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                                //如果需要阻止成功提示，则必须使用return false;
                                                //return false;
                                                //   $(".btn-refresh").trigger("click");

                                            },
                                            error: function (data, ret) {
                                                console.log(data, ret);
                                                Layer.alert(ret.msg);
                                                return false;
                                            }
                                        }]
                                    });
                                    // console.log(Table.api.formatter.operate.call(that,value,row,index));
                                    return Table.api.formatter.buttons.call(that,value,row,index);
                                }
                            }
                        },

                        {field: 'reResume.user_address', title: '住址'},
                        {field: 'reCompany.name', title: '公司名称'},
                        {field: 'reJob.name', title: '岗位名称'},
                        {field: 'offer',  title: '是否录用', searchList: {'1':"已录用", '2': "未录用",'0':'待查看','3':'已查看','4':'通知面试','5':'离职'}, style: 'min-width:100px;'},
                        {field: 'recUser.username', title:'推荐人'},
                        {field: 'update_at', operate: false, title:'更新时间', addclass:'datetimerange'},
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
                                    //   url: options.extend.detail_url
                                });
                            }

                            if (options.extend.detail_url !== '') {
                                buttons.push({
                                    name: 'detail',
                                    icon: 'fa fa-list',
                                    title: '详情',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-detailone',
                                    url: options.extend.detail_url
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
                            if((row.offer_status==0)||(row.offer_status==3)||(row.offer_status==4)){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'录用',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                    });
                                }

                                if (options.extend.deny_url !== '') {
                                    buttons.push({
                                        name: 'deny',
                                        icon: 'fa fa-times',
                                        title:'拒绝',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-denyone',
                                    });
                                }

                            }

                            return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                        }}
                    ]
                ],
                queryParams:function (params) {
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: JSON.stringify({id: $("#default_re_company_id").val()}),
                        op: JSON.stringify({id: '='}),
                        offset: params.offset,
                        limit: params.limit,
                    };
                },
                search:false,
                commonSearch: false,
                showExport: false,

            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        people_detail: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'company/people_detail',
                  /*  add_url: 're/usercomp/add',
                    edit_url: 're/usercomp/edit',
                    del_url: 're/usercomp/del',
                    multi_url: 're/usercomp/multi',
                    pass_url: 're/usercomp/pass',
                    deny_url: 're/usercomp/deny',*/
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
                queryParams:function (params) {
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: JSON.stringify({id: $("#default_re_company_id").val()}),
                        op: JSON.stringify({id: '='}),
                        offset: params.offset,
                        limit: params.limit,
                    };
                },
                search:false,
                commonSearch: false,
                showExport: false,
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        company_detail: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'company/company_detail',
             /*       add_url: 'company/add',
                    edit_url: 'company/edit',
                    //del_url: 'company/del',
                    //  multi_url: 'company/multi',
                    detail_url: 'company/detail',*/
                    table: 're_company',
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
                        /*    {field: 'id', title: __('Id')},*/
                        {field: 'name', title: __('Name')},
                        {field: 'areas', title: __('Areas')},
                        {field: 'address', title: __('Address')},
                        {field: 'mobile', title:'手机号'},
                        {field: 'status', title: '公司状态'},
                        {field: 'yfee', title: '年费', operate:'BETWEEN'},
                        {field: 'account', title: __('Account'), operate:'BETWEEN'},
                        {field: 'levle', title: '公司类型'},

                        {
                            field: 'upload',
                            title: __('公司概要'),
                            table: table,
                            formatter: function (value, row, index) {
                                var that = this;
                                var apply_count = row.info.apply_count;
                                var people_count = row.info.people_count;
                                var company_count = row.info.company_count;
                                var company_id = row.info.id;
                                that = $.extend({}, this);
                                // var url1 = '/admin/re/apply/index?sort=id&order=desc&offset=0&limit=10&filter='+encodeURIComponent('{"re_company_id":"'+"{ids}"+'"}')+'&op='+encodeURIComponent('{"re_company_id":"="}');
                                //  var url11 = encodeURIComponent(url1);

                                that.buttons = [
                                    {
                                        name: 'apply',
                                        url: 'company/apply_detail?re_company_id={ids}',
                                        text: '简历数量:' + apply_count,
                                        extend: 'data-toggle="tooltip" data-placement="bottom"',
                                        title: '',
                                        icon: 'fa fa-upload',
                                        classname: 'btn btn-xs btn-info btn-dialog'
                                    },
                                    {
                                        name: 'people',
                                        url: 'company/people_detail?re_company_id={ids}',
                                        title: '',
                                        extend: 'data-toggle="tooltip" data-placement="bottom"',
                                        text: '员工数量:' + people_count,
                                        icon: 'fa fa-upload',
                                        classname: 'btn btn-xs btn-success btn-dialog'
                                    },
                                    {
                                        name: 'company',
                                        url: 'company/company_detail?re_company_id={ids}',
                                        text: '公司数量:' + company_count ,
                                        extend: 'data-toggle="tooltip" data-placement="bottom"',
                                        title: '',
                                        icon: 'fa fa-upload',
                                        classname: 'btn btn-xs btn-success btn-dialog'
                                    }
                                ];
                                return Table.api.formatter.buttons.apply(that, [value, row, index]);
                            },
                            /*   buttons: [
                             {
                             name: 'upload',
                             url: 'pay/qrcode/add?product_id={ids}',
                             text: '上传价格二维码',
                             extend: 'data-toggle="tooltip" data-placement="left"',
                             title: '请上传固定金额收款二维码',
                             icon: 'fa fa-upload',
                             classname: 'btn btn-xs btn-primary btn-dialog'
                             },
                             {
                             name: 'view',
                             url: 'pay/qrcode/index?product_id={ids}',
                             text: '查看二维码',
                             icon: 'fa fa-eye',
                             classname: 'btn btn-xs btn-warning btn-dialog'
                             }
                             ]*/
                        },




                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter:  function (value, row, index) {
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
                                    //   url: options.extend.detail_url
                                });

                            }
                            if (options.extend.detail_url !== '') {
                                buttons.push({
                                    name: 'detail',
                                    icon: 'fa fa-exchange',
                                    title:'导出小程序码',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-detailone',

                                });
                            }



                            return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                        }}
                    ]
                ],
                queryParams:function (params) {
                    return {
                        search: params.search,
                        sort: params.sort,
                        order: params.order,
                        filter: JSON.stringify({re_company_id: $("#default_re_company_id").val()}),
                        op: JSON.stringify({re_company_id: '='}),
                        offset: params.offset,
                        limit: params.limit,
                    };
                },
                search:false,
                commonSearch: false,
                showExport: false,
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            submit:function () {
                Form.api.submit($("form[role=form]"));
            }
        }
    };


    $("#city-picker").on("cp:update", function() {
    });




    return Controller;
});