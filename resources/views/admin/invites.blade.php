<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Invites</title>
    <link rel="stylesheet" href="{{ asset('css/partyminder.css') }}">

    <style>
        body { font-family: sans-serif; padding: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; }
        th { background: #f0f0f0; }
    </style>



</head>
<body>
    <h1>Guest Invites</h1>

    @if ($invites->isEmpty())
        <p style="text-align: center;">No invites yet.</p>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invites as $invite)
                        <tr>
                            <td>{{ $invite->id }}</td>
                            <td>{{ $invite->name }}</td>
                            <td>{{ $invite->email }}</td>
                            <td>{{ $invite->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</body>

</html>

