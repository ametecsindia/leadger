    
    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            }
            body{
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #006699;
            }
            .wrapper{
            position: relative;
            max-width: 430px;
            width: 100%;
            background: #fff;
            padding: 34px;
            border-radius: 6px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
            }
            .wrapper h2{
            position: relative;
            font-size: 22px;
            font-weight: 600;
            color: #333;
            }
            .wrapper h2::before{
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 28px;
            border-radius: 12px;
            background: #4070f4;
            }
             .input-box{
            height: 52px;
            margin: 18px 0;
            }
            .input-box input, .input-box button{
            height: 100%;
            width: 100%;
            outline: none;
            padding: 0 15px;
            font-size: 17px;
            font-weight: 400;
            color: #333;
            border: 1.5px solid #C7BEBE;
            border-bottom-width: 2.5px;
            border-radius: 6px;
            transition: all 0.3s ease;
            }
            .input-box input:focus{
            border-color: #4070f4;
            }
            .input-box.button button{
            color: #fff;
            letter-spacing: 1px;
            border: none;
            background: #006699;
            cursor: pointer;
            }
            .input-box.button button:hover{
            background: #0e4bf1;
            }
            .validation.captcha{
                margin-top: 7px;
                color: #333;
                background: #ffe5e5;
                display: inline-block;
                padding: 5px 25px;
                border-radius: 17px;
            }
            .button{margin-top:50px;}
            #errormessage{
                background: #fa4738;
                color: #fff;
                text-align: center;
                margin: 8px 0 0;
                padding: 3px 0;
            }
    </style>
    
    <div class="wrapper">
        <h2 id="contact_div">{{ __('frontend.getInTouch') }}</h2>
        <div class="row">

            <div class="col-lg-12">

                @if(isset($response['success']))
                <div id="sendmessage"><i class="fa fa-check-circle"></i>
                    &nbsp;{{ $response['message'] }}</div>
                @elseif(isset($response['error']))
                <div id="errormessage">{{ $response['message'] }}</div>
                @endif

                {{Form::open(['route'=>['registerLeadsPageSubmit'],'method'=>'POST','class'=>'contactForm'])}}
                <div class="input-box">
                    <input id='id' name='id' type='hidden' value='{{$id}}'>
                    {!! Form::text('contact_name','', array('placeholder' => __('frontend.yourName'),'class' => 'form-control','id'=>'name', 'data-msg'=> __('frontend.enterYourName'),'data-rule'=>'minlen:4')) !!}
                    <div class="alert alert-warning validation"></div>
                </div>
                <div class="input-box">
                    {!! Form::text('contact_phone',"", array('placeholder' => __('frontend.phone'),'class' => 'form-control','id'=>'phone', 'data-msg'=> __('frontend.enterYourPhone'),'data-rule'=>'minlen:4')) !!}
                    <div class="validation"></div>
                </div>
                <div class="input-box">
                    <?php $captcha = rand(100000,999999); ?>
                    <input type="hidden" id='verify_captcha' name='verify_captcha' value='{{ md5($captcha) }}'>
                    {!! Form::text('captcha',"", array('placeholder' => 'Enter Below Captcha Code','class' => 'form-control','id'=>'captcha', 'data-msg'=> __('frontend.enterYourPhone'),'data-rule'=>'minlen:6')) !!}
                    <div class="validation captcha">Code: <strong>{{ $captcha }}</strong></div>
                </div>

                @if(env('NOCAPTCHA_STATUS', false))
                    <div class="input-box">
                        {!! NoCaptcha::renderJs(@Helper::currentLanguage()->code) !!}
                        {!! NoCaptcha::display() !!}
                    </div>
                @endif
                <div class="input-box button">
                    <button type="submit" class="btn btn-theme">Get a Callback</button>
                </div>
                <br>
                {{Form::close()}}
            </div>
        </div>
    </div>
