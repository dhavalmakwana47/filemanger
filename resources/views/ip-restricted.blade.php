<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Access Restricted</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 2rem;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            border-radius: 25px;
            padding: 10px 30px;
        }
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            border-radius: 25px;
            padding: 10px 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-shield-alt fa-3x mb-3"></i>
                        <h3>IP Access Restricted</h3>
                        <p class="mb-0">Your IP address is not authorized for this company</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Access Denied:</strong> Your current IP address <code>{{ request()->ip() }}</code> is not in the allowed list for this company.
                        </div>
                        
                        <h5 class="mb-3">Available Options:</h5>
                        
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-building me-2"></i>Switch Company:</label>
                            <form action="{{ route('change_company') }}" method="post" id="company-form">
                                @csrf
                                <select class="form-select mb-3" name="company_id" onchange="document.getElementById('company-form').submit();">
                                    <option value="">Select a different company</option>
                                    @foreach (fetch_company() as $company)
                                        <option value="{{ $company->id }}" {{ get_active_company() == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" value="{{ Route::currentRouteName() }}" name="route">
                            </form>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-muted mb-3">Or</p>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </div>
                        
                        <hr class="my-4">
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Contact your administrator to add your IP address to the allowed list.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>