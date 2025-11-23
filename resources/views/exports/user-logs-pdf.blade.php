<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Logs Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
        }

        h1 {
            color: #2c3e50;
            font-size: 20px;
            margin: 0;
            font-weight: bold;
        }

        .info {
            margin: 8px 0;
            font-size: 10px;
            color: #555;
        }

        .filters {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background-color: #e9ecef;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #ddd;
            white-space: nowrap;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
            font-size: 9.5px;
            line-height: 1.4;
        }

        .text-center {
            text-align: center;
        }

        .wrap-text {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .footer {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="header">
        <h1>User Activity Logs Report</h1>
        <div class="info">
            Exported by: <strong>{{ $exported_by }}</strong> |
            Exported on: <strong>{{ $exported_at }}</strong> |
            Corporate debtor: <strong>{{ $corporate_debtor }}</strong> |
            Person Name: <strong>{{ $personName }}</strong> |
        </div>
    </div>

    <!-- Logs Table -->
    <table>
        <thead>
            <tr>
                <th width="13%">Date & Time</th>
                <th width="15%">User Name</th>
                <th width="35%">Action</th>
                <th width="11%">IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log['date_time'] }}</td>
                    <td>{{ $log['user_name'] }}</td>
                    <td class="wrap">{{ $log['action'] }}</td>
                    <td class="text-center">{{ $log['ip'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 30px; color: #999;">
                        No activity logs found for the selected filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
