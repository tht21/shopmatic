@extends('layouts.app')

@section('content')
    <div class="header bg-gradient-primary py-7 py-lg-8 pt-lg-9">
        <div class="container">
            <div class="header-body text-center mb-7">
                <div class="row justify-content-center">
                    <div class="px-5">
                        <h1 class="text-white">Choose the best plan for your business</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="separator separator-bottom separator-skew zindex-100">
            <svg x="0" y="0" viewBox="0 0 2560 100" preserveAspectRatio="none" version="1.1" xmlns="http://www.w3.org/2000/svg">
                <polygon class="fill-default" points="2560 0 2560 100 0 100"></polygon>
            </svg>
        </div>
    </div>
    <!-- Page content -->
    <div class="container mt--8 pb-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="pricing card-group flex-column flex-md-row mb-3">
                    <div class="card card-pricing border-0 text-center mb-4">
                        <div class="card-header bg-transparent wow fadeIn">
                            <h4 class="text-uppercase ls-1 text-primary py-3 mb-0 wow fadeIn">Free Trial</h4>
                        </div>
                        <div class="card-body">
                            <div class="display-3 wow fadeIn">Free</div>
                            <span class="d-inline-block text-muted wow fadeIn">14 days</span>
                            <p class="mt-3">
                                For individuals who's just started e-commerce and anyone who wants to explore Combinesell.
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="https://app.combinesell.com/register" class="btn btn-primary mb-3 wow fadeInUp d-block mx-auto">Start A Free Trial</a>
                            <a href="#">&nbsp;</a>
                        </div>
                    </div>
                    <div class="card card-pricing border-0 text-center mb-4">
                        <div class="card-header bg-transparent wow fadeIn">
                            <h4 class="text-uppercase ls-1 text-primary py-3 mb-0 wow fadeIn">Starter</h4>
                        </div>
                        <div class="card-body">
                            <div class="display-3 wow fadeIn">S$29.90</div>
                            <span class="d-inline-block text-muted wow fadeIn">per month
{{--                                <i class="fa fa-info-circle" data-toggle="tooltip" title="When billed yearly"></i>--}}
                            </span>
                            <p class="mt-3">
                                For individuals who's just started e-commerce and anyone who wants to explore Combinesell.
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="https://app.combinesell.com/register" class="btn btn-primary mb-3 wow fadeInUp d-block mx-auto">Subscribe Now</a>
                            <a href="#">&nbsp;</a>
                        </div>
                    </div>
                    <div class="card card-pricing border-0 text-center mb-4">
                        <div class="card-header bg-transparent wow fadeIn">
                            <h4 class="text-uppercase ls-1 py-3 mb-0 text-info">Professional<br />
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="display-3 wow fadeIn">S$99.90</div>
                            <span class="d-inline-block text-muted wow fadeIn">per month
{{--                                <i class="fa fa-info-circle" data-toggle="tooltip" title="When billed yearly"></i>--}}
                            </span>
                            <p class="mt-3">
                                Businesses requiring more features, advanced reporting and prioritized support.
                            </p>
                            <ul class="list-unstyled my-4">
                                <li class="wow fadeInRight">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div class="pl-2 text-left">
                                            <span>Multichannel Sales Performance</span>
                                        </div>
                                    </div>
                                </li>
{{--                                <li class="wow fadeInRight" data-wow-delay="0.2s">--}}
{{--                                    <div class="d-flex align-items-center">--}}
{{--                                        <div>--}}
{{--                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">--}}
{{--                                                <i class="fas fa-check"></i>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div>--}}
{{--                                            <span class="pl-2">Multichannel Enquiry Management</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </li>--}}
                                <li class="wow fadeInRight" data-wow-delay="0.4s">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="pl-2">Dedicated Support</span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="https://app.combinesell.com/register" class="btn btn-info mb-3 wow fadeInUp d-block mx-auto">Subscribe Now</a>
                            <a href="#">&nbsp;</a>
                        </div>
                    </div>
                    <div class="card card-pricing border-0 text-center mb-4">
                        <div class="card-header bg-transparent wow fadeIn">
                            <h4 class="text-uppercase ls-1 py-3 mb-0 text-success">Advanced<br />
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="display-3 wow fadeIn">S$149.90</div>
                            <span class="d-inline-block text-muted wow fadeIn">per month
{{--                                <i class="fa fa-info-circle" data-toggle="tooltip" title="When billed yearly"></i>--}}
                            </span>
                            <p class="mt-3">
                                Businesses requiring more features, advanced reporting and prioritized support.
                            </p>
                            <ul class="list-unstyled my-4">
                                <li class="wow fadeInRight">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div class="pl-2 text-left">
                                            <span>Multichannel Sales Performance</span>
                                        </div>
                                    </div>
                                </li>
                                {{--                                <li class="wow fadeInRight" data-wow-delay="0.2s">--}}
                                {{--                                    <div class="d-flex align-items-center">--}}
                                {{--                                        <div>--}}
                                {{--                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">--}}
                                {{--                                                <i class="fas fa-check"></i>--}}
                                {{--                                            </div>--}}
                                {{--                                        </div>--}}
                                {{--                                        <div>--}}
                                {{--                                            <span class="pl-2">Multichannel Enquiry Management</span>--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}
                                {{--                                </li>--}}
                                <li class="wow fadeInRight" data-wow-delay="0.4s">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="pl-2">Dedicated Support</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="wow fadeInRight" data-wow-delay="0.4s">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="pl-2">Unlimited Sku</span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="https://app.combinesell.com/register" class="btn btn-success mb-3 wow fadeInUp d-block mx-auto">Subscribe Now</a>
                            <a href="#">&nbsp;</a>
                        </div>
                    </div>
                    <div class="card card-pricing border-0 text-center mb-4">
                        <div class="card-header bg-transparent">
                            <h4 class="text-uppercase ls-1 text-orange py-3 mb-0 wow fadeIn">Enterprise</h4>
                        </div>
                        <div class="card-body">
                            <div class="display-3 wow fadeIn">S$2,100.00</div>
                            <span class="d-inline-block text-muted wow fadeIn">per month*</span>
                            <p class="mt-4 pt-3">
                                For companies that wants to grow and scale their business easily with Combinesell.
                            </p>
                            <ul class="list-unstyled my-4">
                                <li class="wow fadeInRight">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="pl-2">Support Staff</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="wow fadeInRight" data-wow-delay="0.2s">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="pl-2">Campaign Handling</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="wow fadeInRight" data-wow-delay="0.4s">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="pl-2">Product Listing</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="wow fadeInRight" data-wow-delay="0.4s">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="pl-2">Product Design</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="wow fadeInRight" data-wow-delay="0.6s">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="icon icon-xs icon-shape bg-success shadow rounded-circle text-white">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="pl-2">5% revenue sharing</span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="card-footer">

                            <a href="{{ route('contact.index') }}" class="btn bg-orange text-white mb-3 wow fadeInUp d-block">Contact Us</a>
                            <a href="{{ route('enterprise.index') }}" target="_blank" class="text-primary wow fadeIn">Learn More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-lg-center px-3 mt-5 wow fadeIn">
            <div>
                <div class="icon icon-shape bg-gradient-white shadow rounded-circle text-primary">
                    <i class="ni ni-building text-primary"></i>
                </div>
            </div>
            <div class="col-lg-7">
                <p class="text-white">We also offer custom packages. Contact <a href="mailto:sales@combinesell.com" class="text-info">sales@combinesell.com</a> for more information!</p>
            </div>
        </div>
        <div class="row row-grid justify-content-center">
            <div class="col-lg-10">
                <div class="table-responsive">
                    <table class="table table-dark mt-5">
                        <thead>
                        <tr>
                            <th class="px-0 bg-transparent" scope="col">
                                <span class="text-light font-weight-700">Features</span>
                            </th>
                            <th class="text-center bg-transparent" scope="col">Starter</th>
                            <th class="text-center bg-transparent" scope="col">Professional</th>
                            <th class="text-center bg-transparent" scope="col">ADVANCED</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="px-0">Multichannel Listing Management</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-0">Multichannel Bulk Export</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-0">Multichannel Order Management</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-0">Multichannel Inventory Sync</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                        </tr>
                        <!-- <tr>
                            <td class="px-0">Multichannel Enquiry Management</td>
                            <td class="text-center">-</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                        </tr> -->
                        <tr>
                            <td class="px-0">Multichannel Profit & Loss</td>
                            <td class="text-center">-</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-0">Dedicated Support</td>
                            <td class="text-center">-</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                            <td class="text-center"><i class="fas fa-check text-success"></i>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-0">Additional Users</td>
                            <td class="text-center">-</td>
                            <td class="text-center text-success font-weight-bold">3</td>
                            <td class="text-center text-success font-weight-bold">10</td>
                        </tr>
                        <tr>
                            <td class="px-0">Integrations</td>
                            <td class="text-center text-success font-weight-bold">3</td>
                            <td class="text-center text-success font-weight-bold">10</td>
                            <td class="text-center text-success font-weight-bold">10</td>
                        </tr>
                        <tr>
                            <td class="px-0">SKUs</td>
                            <td class="text-center text-success font-weight-bold">1000</td>
                            <td class="text-center text-success font-weight-bold">8000</td>
                            <td class="text-center text-success font-weight-bold">unlimited</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
