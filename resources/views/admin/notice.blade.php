@extends('admin/base_template/dashboard')
@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ url('/admin/notice') }}" method="post">
        @csrf
    <div class="row">
        <div class="col-md-3">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">公告标题</h3>

                    <div class="box-tools">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <input type="text" name="title" class="form-control" placeholder="请输入标题">
                </div>
            </div>
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">重要等级</h3>

                    <div class="box-tools">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <ul class="nav nav-pills nav-stacked">
                        <li>
                            <a href="#">
                                <input type="radio" name="type" value="1" checked="">
                                重要
                                <i class="fa fa-circle-o text-red pull-right"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <input type="radio" name="type" value="2">
                                次要
                                <i class="fa fa-circle-o text-yellow pull-right"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <input type="radio" name="type" value="2">
                                普通
                                <i class="fa fa-circle-o text-light-blue pull-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <button class="btn btn-info btn-block margin-bottom" name="submit" type="submit">发布公告</button>
        </div>
        <div class="col-md-9">
            <div class="box box-info">
                <div class="box-header">
                    <h3 class="box-title">公告编辑
                        <small>在以下编辑框中填写您需要发布的公告</small>
                    </h3>
                    <!-- tools box -->
                    <div class="pull-right box-tools">
                        <button type="button" class="btn btn-info btn-sm" data-widget="collapse" data-toggle="tooltip"
                                title="" data-original-title="Collapse">
                            <i class="fa fa-minus"></i></button>
                        <button type="button" class="btn btn-info btn-sm" data-widget="remove" data-toggle="tooltip"
                                title="" data-original-title="Remove">
                            <i class="fa fa-times"></i></button>
                    </div>
                    <!-- /. tools -->
                </div>
                <div class="box-body pad">
                    <textarea id="editor" name="editor" rows="10" cols="160" style="visibility: hidden; display: none;"></textarea>
                </div>
            </div>
        </div>
    </div>
    </form>
    @push('notice-js')
        <!-- ckeditor -->
        <script src="{{ asset("/bower_components/ckeditor/ckeditor.js") }}"></script>
        <script src="{{ asset("/bower_components/ckeditor/config.js") }}"></script>
        <script src="{{ asset("/bower_components/ckeditor/styles.js") }}"></script>
        <script src="{{ asset("/bower_components/ckeditor/lang/zh-cn.js") }}"></script>
        <script src="{{ asset("/bower_components/ckeditor/plugins/colorbutton/plugin.js") }}"></script>
        <script>
            $(document).ready(function(){
                CKEDITOR.replace('editor',{
                    height: 600,
                    filebrowserUploadUrl: '{{url('admin/uploadImage')}}?_token={{csrf_token()}}',
                });
            });
        </script>
    @endpush
@endsection
