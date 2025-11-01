<style>
    /* Font Import */
    @import url("https://fonts.googleapis.com/css?family=Montserrat:300,400,600,700|Nunito:300,400,600,700&display=swap");

    /* Global Styles */
    body {
        font-family: "Nunito", sans-serif;
        transition: all 0.4s ease-in-out;
        background: #f9f9f9;
        color: #333;
        text-align: center;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: "Montserrat", sans-serif;
    }

    .page-wrap {
        padding: 40px 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
    }

    .page-not-found {
        max-width: 450px;
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .img-key {
        width: 80px;
        margin-bottom: 20px;
    }

    h1.text-xl {
        font-size: 140px;
        font-weight: 800;
        letter-spacing: -10px;
        text-shadow: -4px 3px 0px #fff;
        color: #000;
        margin: 20px 0;
    }

    h1.text-xl span {
        display: inline-block;
        animation: pulse 4s infinite alternate;
    }

    h4.text-md {
        font-size: 28px;
        font-weight: 700;
        color: #1177bd;
        margin-bottom: 10px;
    }

    h4.text-sm {
        font-size: 15px;
        color: rgba(0, 0, 0, 0.6);
        line-height: 1.5;
    }

    h4.text-sm a {
        color: #1177bd;
        font-weight: 700;
        text-decoration: none;
    }

    h4.text-sm a:hover {
        text-decoration: underline;
    }

    /* Dropdown */
    select.form-select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-top: 15px;
        background: #fff;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
    }

    select.form-select:focus {
        border-color: #1177bd;
        box-shadow: 0 0 5px rgba(17, 119, 189, 0.4);
    }

    /* Animation */
    @keyframes pulse {
        0% { color: #000; }
        50% { color: #f03030; }
        100% { color: #000; }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        h1.text-xl {
            font-size: 100px;
            letter-spacing: -6px;
        }

        h4.text-md {
            font-size: 22px;
        }

        h4.text-sm {
            font-size: 14px;
        }

        .page-not-found {
            padding: 20px;
        }
    }

    
</style>

<div class="page-wrap">
    <div class="page-not-found">
        <img src="https://res.cloudinary.com/razeshzone/image/upload/v1588316204/house-key_yrqvxv.svg" class="img-key" alt="">

        <h1 class="text-xl">
            <span>4</span>
            <span>0</span>
            <span class="broken">3</span>
        </h1>

        <h4 class="text-md">Access Denied!</h4>
        <h4 class="text-sm">You don’t have access to this area. Speak to your administrator to unblock this feature. 
            <br>Go back to <a href="/">Home Page</a>
        </h4>

        <form action="{{ route('change_company') }}" method="post" id="company-form">
            @csrf
            <select class="form-select" name="company_id" onchange="document.getElementById('company-form').submit();">
                <option value="">Select Company</option>
                @foreach (fetch_company() as $company)
                    <option value="{{ $company->id }}" {{ get_active_company() == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" value="{{ Route::currentRouteName() }}" name="route"> 
        </form>
    </div>
</div>
