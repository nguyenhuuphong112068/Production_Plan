
<form 
      action="{{route('upload.import')}}" 
      method="POST" enctype="multipart/form-data">
      
      @csrf
      <label>Chọn file Excel:</label>
      <input type="file" name="excel_file" accept=".xlsx, .xls" required>
      <input type="text" name="table" required>
      <button type="submit" name="import">Import</button>
      
  </form>