@extends('admin/base_template/dashboard')
@section('content')
	@if(!empty(session('success')))
        <div class="alert alert-success" role="alert">
        	{{session('success')}}
        </div>
	@endif
	@if(!empty(session('fail')))
        <div class="alert alert-danger" role="alert">
        	{{session('fail')}}
        </div>
    @endif
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header"><h5 class="box-title">个人设置</h5></div>
                <div class="box-body">
                    <form method="post" action="{{ route('user.release_task.publish') }}">
                    @csrf
                    <input type="hidden" value="1" name="step">
                    <input type="hidden" value="{{ session('tasktype') }}" name="step">
                    <input type="hidden" value="{{ session('sid') }}" name="step">
                    <a class="btn btn-info pull-right" href="{{ route('user.release_task',['step'=>1]) }}">
                        <i class="fa fa-fast-forward"></i> 上一步
                    </a>
                    </form>
                    
                </div>
            </div>
        </div>
	</div>
@endsection