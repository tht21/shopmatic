@extends('layouts.app')

@section('content')
    <div class="header bg-gradient-primary"><div class="separator separator-bottom separator-skew zindex-100"></div></div>
    <section>
        <div class="container-fluid py-5 header-bg">
            <div class="container py-5">
                <div class="row">
                    <div class="col-md-6 py-5">
                        <h1 class="font-weight-light font-size-40 text-white wow fadeInRight mb-0">Sell Everywhere <br />Your Customers Shop</h1>
                        <h1 class="font-weight-light text-white wow fadeInLeft mt-0 mb-5">Singapore's #1 Multichannel E-Commerce Solution</h1>

                        <a class="btn btn-outline-white wow fadeIn" href="{{ route('contact.index') }}">Request Demo</a><a class="btn btn-primary ml-3 wow fadeIn" data-wow-delay="0.2s" href="{{ route('end-to-end.index') }}">Request Free Consultation</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-3">
        <div class="container-fluid">
            <div class="row text-center">
                <div class="col-12">
                    <h2 class="mt-4 text-light wow fadeIn">As Featured On</h2>

                    <div class="my-4 justify-content-center">
                        <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.2s" src="/images/seen/2.png">
                        <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.4s" src="/images/seen/4.png">
                        <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.6s" src="/images/seen/6.png">
                        <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.5s" src="/images/seen/7.png">
                        <img alt="image" height="20" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.3s" src="/images/seen/3.png">
                        <img alt="image" height="20" class="ml-3 mr-3 mb-2 mt-2 wow fadeIn" data-wow-delay="0.5s" src="/images/seen/5.png">
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="p-5 bg-white">
        <div class="container py-5">
            <div class="row text-left align-items-center">
                <div class="col-12 col-md-5 pt-4 pt-md-0">
                    <h1 class="font-weight-light mb-4 wow fadeInDown">One Platform to Sell Everywhere</h1>
                    <p class="wow fadeInUp text-muted">Gone are the days you need to use multiple websites to do the <b>same</b> thing. With CombineSell, you can do everything in <strong>one</strong> place.</p>
                </div>

                <div class="col-12 col-md-7 pt-5 pt-lg-0">
                    <img alt="image" class="img-fluid mt-5 wow slideInRight" src="/images/email.png">
                </div>
            </div>
        </div>
    </section>
    <section class="bg-lightest p-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-12 col-md-5 m-md-auto ml-lg-0 mr-lg-auto">
                    <img alt="image" class="img-fluid wow flipInX" src="/images/features.svg">
                </div>
                <div class="col-12 col-md-7 ml-sm-auto pt-5 pt-lg-0 pl-5">
                    <h1 class="text-default wow pulse">Why CombineSell?</h1>

                    <div class="row pt-4 pt-xl-5">
                        <div class="col-12 col-sm-6 col-xl-5 wow fadeIn" data-wow-delay="0.1s">
                            <h4>Product Information<br /> Management (PIM)</h4>
                            <p>Easily manage your product information on every platform</p>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-5 m-auto pt-3 pt-sm-0 wow fadeIn" data-wow-delay="0.2s">
                            <h4>Inventory <br /> Management</h4>
                            <p>View and adjust your stock across all platforms</p>
                        </div>
                    </div>

                    <div class="row pt-3">
                        <div class="col-12 col-sm-6 col-xl-5 wow fadeIn" data-wow-delay="0.3s">
                            <h4>Enquiry <br /> Management</h4>
                            <p>Retrieve and answer all your enquiries easily</p>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-5 m-auto pt-3 pt-sm-0 wow fadeIn" data-wow-delay="0.4s">
                            <h4>Order & Sales <br /> Management</h4>
                            <p>Fulfill your entire order from every platform</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="p-5 full-background" style="background-image: url('images/background/orange.svg');" >
        <div class="container align-items-center justify-content-center d-flex">
            <div class="row justify-content-center text-center">
                <div class="col-12 col-md-10">
                    <h1 class="text-white wow fadeInDown">Join the thousands of users using our platform!</h1>
                    <p class="mt-5"><a href="{{ route('end-to-end.index') }}" class="btn btn-outline-white wow fadeInUp">Request Free Consultation</a></p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-lightest py-5" >
        <div class="container">
            <h1 class="font-weight-light wow fadeInDown text-center">3 Step Setup!</h1>
            <h3 class="font-weight-light text-center wow fadeInUp" data-wow-delay="0.3s">Kickstart your operations with these simple steps.</h3>

            <div class="row text-center mt-5">

                <div class="col-12 col-md-8 col-lg-4 pt-5 pt-lg-0 align-self-stretch">
                    <div class="card full-height wow fadeInUp">
                        <div class="card-body border-top-cyan">

                            <i class="fa fa-link text-cyan fa-4x"></i>
                            <h2 class="mt-3">Integrate</h2>
                            <p>Add all of your integrations in our platform.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-8 col-lg-4 pt-5 pt-lg-0 align-self-stretch">
                    <div class="card full-height wow fadeInUp" data-wow-delay="0.3s">
                        <div class="card-body border-top-primary">

                            <i class="fa fa-sync-alt text-primary fa-4x"></i>
                            <h2 class="mt-3">Synchronise</h2>
                            <p>Automatically sync all your products between the integrations.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-8 col-lg-4 pt-5 pt-lg-0 align-self-stretch">
                    <div class="card full-height wow fadeInUp" data-wow-delay="0.6s">
                        <div class="card-body border-top-success">

                            <i class="fa fa-money-bill-wave text-success fa-4x"></i>
                            <h2 class="mt-3">Sell Everywhere</h2>
                            <p>You can now sell your products to all platforms!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-5 text-muted bg-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-12">
                    <h1 class="font-weight-light wow fadeInDown">Integrations</h1>
                    <p class="text-muted wow fadeInUp">Integrate with the different types of integrations! View the <a href="{{ route('integrations.index') }}" class="font-weight-bold">full list here</a>!</p>
                </div>
            </div>

            <div class="row text-center justify-content-center pt-2">

                <div class="col-12 col-md pt-4 wow fadeInDown" data-wow-delay="0.15s">
                    <i class="fa fa-sitemap text-cyan fa-4x"></i>
                    <h3 class="mt-3">Websites</h3>
                </div>
                <div class="col-12 col-md pt-4 wow fadeInDown" data-wow-delay="0.3s">
                    <i class="fa fa-store-alt text-purple fa-4x"></i>
                    <h3 class="mt-3">Marketplaces</h3>
                </div>
                <div class="col-12 col-md pt-4 wow fadeInDown" data-wow-delay="0.45s">
                    <i class="fa fa-money-check-alt fa-4x text-success"></i>
                    <h3 class="mt-3">Accounting</h3>
                </div>
                <div class="col-12 col-md pt-4 wow fadeInDown" data-wow-delay="0.6s">
                    <i class="fa fa-cube fa-4x text-info"></i>
                    <h3 class="mt-3">Warehousing</h3>
                </div>
                <div class="col-12 col-md pt-4 wow fadeInDown" data-wow-delay="0.75s">
                    <i class="fa fa-truck-pickup text-blue fa-4x"></i>
                    <h3 class="mt-3">Logistics</h3>
                </div>
            </div>
        </div>
    </section>
    <section class="py-5 full-background" style="background-image: url('images/background/blue.svg');">
        <div class="container">
            <div class="row text-center justify-content-center">
                <div class="col-md-10 col-lg-8 col-xl-7 text-white">
                    <h1 class="text-white wow fadeInDown">Testimonials</h1>
                    <p class="wow fadeInUp">What our customers say about us</p>
                </div>
            </div>

            <div class="row mt-5 align-items-center justify-content-center">
                <div class="col-md-8 col-lg-4 d-flex align-self-stretch">
                    <div class="card full-height wow fadeInDown">
                        <div class="card-body d-flex flex-column full-height">
                            <div class="row mt-4 flex-grow-1">
                                <div class="col-12">
                                    <p>
                                        "CombineSell helps our company to simplify the multichannel selling with super supportive and professional assistance. Thanks to CombineSell, we don't have to manage all marketplaces with complex user interface individually and manually anymore."
                                    </p>
                                </div>
                            </div>
                            <div class="row no-gutters align-items-center">
                                <p>
                                    <strong>Leo Zhang</strong><br>
                                    <em>Director at NiceDeal.sg</em>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-4 d-flex align-self-stretch">
                    <div class="card full-height wow fadeInDown" data-wow-delay="0.3s">
                        <div class="card-body d-flex flex-column">
                            <div class="row mt-4 flex-grow-1">
                                <div class="col-12">
                                    <p>
                                        "Ever since we have engaged CombineSell, our sales grew expeditiously by tapping on multichannel e-commerce selling. We are super grateful to have the professional team to assist us. Thanks CombineSell!"
                                    </p>
                                </div>
                            </div>
                            <div class="row no-gutters align-items-center">
                                <p>
                                    <strong>Joseph Goh</strong><br>
                                    <em>Director at Biogreen</em>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-4 d-flex align-self-stretch">
                    <div class="card full-height wow fadeInDown" data-wow-delay="0.6s">
                        <div class="card-body d-flex flex-column">
                            <div class="row mt-4 flex-grow-1">
                                <div class="col-12">
                                    <p style="font-size: 0.90rem">
                                        "Engaging CombineSell has really make a great impact and change to our company. Tapping on the multichannel e-commerce has always been our plan to do so, with CombineSell, we are able to expedite on the progress and work together hand in hand to do better online! It’s never too late to start, but it will be regretful if we don’t tap on them soon enough! Thanks, CombineSell!"
                                    </p>
                                </div>
                            </div>
                            <div class="row no-gutters align-items-center">
                                <p>
                                    <strong>Ivy Ang</strong><br>
                                    <em>Director at Mattress International</em>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="bg-white p-5">
        <div class="container-fluid">
            <div class="row pb-3">
                <div class="col-12 text-center wow fadeInDown">
                    <p><strong>Trusted By</strong></p>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-center">
                    <img alt="image" height="30" class="mr-3 mb-2 mt-2 wow fadeInDown" data-wow-delay="0.1s" src="/images/trusted/2.png">
                    <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeInDown" data-wow-delay="0.2s" src="/images/trusted/3.png">
                    <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeInDown" data-wow-delay="0.3s" src="/images/trusted/4.png">
                    <img alt="image" height="30" class="ml-3 mr-2 mb-2 mt-2 wow fadeInDown" data-wow-delay="0.4s" src="/images/trusted/1.png">
                    <img alt="image" height="30" class="ml-2 mr-2 mb-2 mt-2 wow fadeInDown" data-wow-delay="0.5s" src="/images/trusted/5.png">
                    <img alt="image" height="30" class="ml-2 mr-2 mb-2 mt-2 wow fadeInDown" data-wow-delay="0.6s" src="/images/trusted/6.png">
                    <img alt="image" height="30" class="ml-3 mr-3 mb-2 mt-2 wow fadeInDown" data-wow-delay="0.7s" src="/images/trusted/7.png">
                </div>
            </div>
        </div>
    </section>
@endsection