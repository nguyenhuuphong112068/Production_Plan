
<form 
      action="{{route('upload.import')}}" 
      method="POST" enctype="multipart/form-data">
      
      @csrf
      <label>Chọn file Excel:</label>
      <input type="file" name="excel_file" accept=".xlsx, .xls" required>
      <input type="text" name="table" required>
      <button type="submit" name="import">Import</button>
      
  </form>

  <form 
      action="{{route('upload.import_permission')}}" 
      method="POST" enctype="multipart/form-data">
      @csrf
      <button type="submit" name="import">import_permission</button>
      
  </form>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif