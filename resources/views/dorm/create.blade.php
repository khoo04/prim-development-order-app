@extends('layouts.master')

@section('css')
<link href="{{ URL::asset('assets/libs/chartist/chartist.min.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')

{{-- <p>Welcome to this beautiful admin panel.</p> --}}
<div class="row align-items-center">
    <div class="col-sm-6">
        <div class="page-title-box">
            <h4 class="font-size-18">Tambah Permintaan</h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Asrama >> Tambah Permintaan</li>
            </ol>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">

            @if(count($errors) > 0)
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                    <li>{{$error}}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <form method="post" action="{{ route('dorm.store') }}" enctype="multipart/form-data">
                {{csrf_field()}}
                <div class="card-body">
                    <div class="form-group">
                        <label>Nama Organisasi</label>
                        <select name="organization" id="organization" class="form-control">
                            @foreach($organization as $row)
                                @if ($loop->first)
                                <option value="{{ $row->id }}" selected>{{ $row->nama }}</option>
                                @else
                                <option value="{{ $row->id }}">{{ $row->nama }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nama Pelajar</label>
                        <select name="name" id="name" class="form-control">
                            <option value="" selected disabled>Pilih Pelajar</option>
                            @foreach($student as $row)
                                <option value="{{ $row->id }}">{{ $row->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- <div class="form-group">
                        <label>Email Pelajar</label>
                        <input type="text" name="email" class="form-control" placeholder="Email Pelajar">
                    </div> -->

                    <input id="outingdate" value="{{$outingdate}}" hidden>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category" id="category" class="form-control">
                            <option value="" disabled selected>Pilih Kategori Keluar</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Alasan</label>
                        <textarea name="reason" class="form-control" placeholder="Alasan Keluar" max-length="50" cols="30"
                            rows="5"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Tarikh Keluar</label>
                        <input onclick="this.showPicker()" class="form-control" id="start_date" name="start_date" type="date"
                                placeholder="Pilih Tarikh Keluar">
                    </div>

                    <div class="form-group mb-0">
                        <div>
                            <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->
            </form>
        </div>
    </div>
</div>
@endsection


@section('script')
<!-- Peity chart-->
<script src="{{ URL::asset('assets/libs/peity/peity.min.js')}}"></script>

<!-- Plugin Js-->
<script src="{{ URL::asset('assets/libs/chartist/chartist.min.js')}}"></script>

<script src="{{ URL::asset('assets/js/pages/dashboard.init.js')}}"></script>

<script>
    $(document).ready(function() {
        // do ajax call function to get the category for user based on dorm value
        
        $("#name").prop("selectedIndex", 1).trigger('change');
        fetchCategory($("#name").val());

        $('#name').change(function() {
            
            if($(this).val() != '')
            {
                var studentid = $("#name option:selected").val();
                var _token = $('input[name="_token"]').val();
                $.ajax({
                    url:"{{ route('dorm.fetchCategory') }}",
                    method:"GET",
                    data:{ sid:studentid,
                            oid:$("#organization option:selected").val(),
                            _token:_token },
                    success:function(result)
                    {  
                        $('#category').empty();
                        $("#category").append("<option value='' disabled selected> Pilih Kategori Keluar</option>");
                        jQuery.each(result.success, function(key, value){
                            $("#category").append("<option value='"+ value.id + "‡" + value.day_before + "‡" + value.name +"'>" + value.fake_name + "</option>");
                        });
                    }

                });
            }
        });

        function fetchCategory(studentid = ''){
            var _token = $('input[name="_token"]').val();
            $.ajax({
                url:"{{ route('dorm.fetchCategory') }}",
                method:"GET",
                data:{ sid:studentid,
                    oid:$("#organization option:selected").val(),
                        _token:_token },
                success:function(result)
                {
                    $('#category').empty();
                    $("#category").append("<option value='' disabled selected> Pilih Kategori Keluar </option>");
                    jQuery.each(result.success, function(key, value){
                        $("#category").append("<option value='"+ value.id + "‡" + value.day_before + "‡" + value.name +"'>" + value.fake_name + "</option>");
                    });
                }
            })
        }
        
    });

    
    

</script>
@endsection

