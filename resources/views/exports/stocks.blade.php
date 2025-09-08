<div class="title" style="padding-bottom: 13px">
    <div style="text-align: center;text-transform: uppercase;font-size: 15px">
        Warung Mbok Sum
    </div>
    <div style="text-align: center">
        Alamat: Desa Sragi Kec. Sragi
    </div>
    <div style="text-align: center">
        Telp: 0851-5524-7723
    </div>
</div>
<table style="width: 100%">
    <thead>
        <tr style="background-color: #e6e6e7;">
            <th scope="col">No</th>
            <th scope="col">Nama Barang</th>
            <th scope="col">Kategori</th>
            <th scope="col">Qty Awal</th>
            <th scope="col">Qty + </th>
            <th scope="col">Jumlah</th>
            <th scope="col">User</th>
            <th scope="col">Keterangan</th>
            <th scope="col">Tanggal Update</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
        @php($entry = $product->latestStockEntry)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $product->title }}</td>
            <td>{{ $product->category->name ?? '-' }}</td>
            <td>{{ $entry?->before_stock ?? '-' }}</td>
            <td>{{ $entry?->quantity ?? '-' }}</td>
            <td>{{ $entry?->after_stock ?? $product->stock }}</td>
            <td>{{ $entry?->user?->name ?? '-' }}</td>
            <td>{{ $entry?->note ?? '-' }}</td>
            <td>{{ $entry?->created_at?->format('d-M-Y H:i:s') ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>