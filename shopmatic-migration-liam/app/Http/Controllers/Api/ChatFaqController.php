<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class ChatFaqController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = $this->faqData();
        return $this->respond($query);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function faqData()
    {

        $data = [];

        foreach (range(1, 18) as $i) {

            array_push($data, [
                'id' => $i,
                'name' => 'Category ' . $i,
                'icon' => '/images/knowledgebase/1.jpg',
                'items' => [
                    [
                        'name' => 'Title Question 1?',
                        'questions' => [
                            'question 1',
                            'question 2',
                            'question 3',
                        ]
                    ],
                    [
                        'name' => 'Title Question 2?',
                        'questions' => [
                            'question 4',
                            'question 5',
                            'question 6',
                        ]
                    ],
                    [
                        'name' => 'Title Question 3?',
                        'questions' => [
                            'question 7',
                            'question 8',
                            'question 9',
                        ]
                    ],
                ],
            ]);
        }

        return $data;
    }
}
