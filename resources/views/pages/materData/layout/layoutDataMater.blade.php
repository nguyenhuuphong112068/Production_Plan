@extends ('layout.master')

@section('topNAV')
    @include('pages.materData.layout.topNAV')
@endsection

@section('leftNAV')
    @include('pages.materData.layout.leftNAV')
@endsection


@section('mainContent')
    @include('pages.materData.Testing.list') 
</div>

@endsection