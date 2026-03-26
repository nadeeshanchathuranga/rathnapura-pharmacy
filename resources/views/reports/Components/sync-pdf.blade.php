<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sync Report</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 9px; 
            margin: 15px; 
        }
        h2 { 
            color: #333; 
            margin-bottom: 3px; 
            font-size: 16px;
        }
        .period { 
            color: #666; 
            margin-bottom: 8px; 
            font-size: 8px;
        }
        .filters {
            background: #f5f5f5;
            padding: 6px;
            margin-bottom: 10px;
            font-size: 8px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px;
            font-size: 8px;
        }
        th, td { 
            border: 1px solid #ccc; 
            padding: 4px; 
            text-align: left; 
        }
        th { 
            background: #e8e8e8; 
            font-weight: bold;
        }
        .summary-box {
            margin-top: 10px;
            padding: 8px;
            background: #dcfce7;
            font-size: 8px;
        }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; padding: 10px; border-top: 1px solid #ccc; background-color: #f9fafb; }
    </style>
</head>
<body>
    <h2>Sync Report</h2>
    <p class="period">Period: {{ $startDate }} to {{ $endDate }}</p>
    
    <div class="filters">
        <strong>Filters:</strong> User: {{ $selectedUser }}
    </div>
    
    @if($logs->isEmpty())
        <p style="text-align: center; color: #999; padding: 20px;">No sync logs found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 15%;">Date & Time</th>
                    <th style="width: 15%;">User</th>
                    <th style="width: 12%;">Module</th>
                    <th style="width: 15%;">Action</th>
                    <th style="width: 38%;">Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log['id'] }}</td>
                        <td>{{ $log['created_at'] }}</td>
                        <td>{{ $log['user_name'] }}</td>
                        <td>{{ $log['module'] }}</td>
                        <td>{{ $log['action'] }}</td>
                        <td>{{ substr($log['details'], 0, 100) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="summary-box">
            <p><strong>Summary:</strong> Total: {{ $logs->count() }} | Period: {{ $startDate }} to {{ $endDate }}</p>
        </div>
    @endif
    <div class="footer">
        <p>Powered by JAAN Network (PVT) Ltd</p>
    </div>
</body>
</html>
