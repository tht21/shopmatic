<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\Request;

class RetailController extends Controller
{
    public function index(Request $request)
    {
        $calculate = $this->calculateRevenue();
        return $this->respond($calculate);
    }

    public function calculateRevenue()
    {
        return Order::sum('grand_total');
    }

    public function countSales()
    {
        return Order::count('id');
    }

    public function countCustomers()
    {
        //Total Customers = No of Unique Customers Registered in this period
    }

    public function calculateGrossProfit(Order $order)
    {
        //Total Gross Profit = Total Revenue - Cost of Goods Sold
    }

    public function calculateDiscount()
    {
        //Dicount = Discount Rate * Original Price

        //list out all products with discount
        //calculate discount for each product
        //sum products with dicount

        //return discount amount, percentage
    }

    public function calculateBasketValue()
    {
        //Avg revenue per sales
        //Avg Order Value = Total Revenue/No of Orders
    }

    public function calculateBasketSize()
    {
        //basket size refers to the number of items getting sold in a single purchase
        //Avg Order Size = No of purchases/ No of Footfalls
        //Avg Order Size = Total Units Sold/ No of Invoices
        //Average no of items per sales
    }
}
