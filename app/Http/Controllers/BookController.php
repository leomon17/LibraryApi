<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookReview;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class BookController extends Controller
{
    public function BookController(){

    }

    public function index(){
        //$books = Book::all();
        $books = Book::with('authors','category','editorial')->get();
        return [
            "error" => false,
            "message" => "Successfull query--",
            "data" => $books
        ];
    }

    public function store(Request $request){
        DB::beginTransaction();
        try {
            $response = $this->getResponse();
            $existIsbn = Book::where('isbn', $request->isbn)->exists();
            if (!$existIsbn) {
                $book = new Book();
                $book->isbn = trim($request->isbn);
                $book->title = trim($request->title);
                $book->description = trim($request->description);
                $book->category_id = trim($request->category["id"]);
                $book->editorial_id = trim($request->editorial["id"]);
                $book->publish_date = Carbon::now();
                $book->save();
                foreach($request->authors as $item){
                    $book->authors()->attach($item);
                }

                $bookID = $book->id;

                $response["Estatus"] = 1;
                $response["Mensaje"] = "Yout book has been created";
                $response["data"] =
                        ["book" => $book,
                        "Id" => $bookID];
            }else{
                $response["Estatus"] = 0;
                $response["Mensaje"] = "Data error";
            }

            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            return [
                "Estatus" => 0,
                "Mensaje" => "Data error",
                "data" => "",
            ];
        }


    }

    public function update(Request $request, $id){
        DB::beginTransaction();
        try {
            $response = $this->getResponse();
            $book = Book::find($id);
            if ($book) {
                $isbnOwner = Book::where("isbn", $request->isbn)->first();
                if ( !$isbnOwner || $isbnOwner->id == $id) {
                    $book->isbn = trim($request->isbn);
                    $book->title = trim($request->title);
                    $book->description = trim($request->description);
                    $book->category_id = trim($request->category["id"]);
                    $book->editorial_id = trim($request->editorial["id"]);
                    $book->publish_date = Carbon::now();
                    $book->save();
                    foreach($book->authors as $item){
                        $book->authors()->detach($item->id);
                    }

                    foreach($request->authors as $item){
                        $book->authors()->attach($item);
                    }
                    $book = Book::with('category','editorial','authors')->where("id",$id)->get();
                    $response["data"] = $book;
                }else{
                    $response["error"] = true;
                    $response["message"] ="ISBN Duplicated";
                }

            }else{
                $response["error"] = true;
                $response["message"] =" 404 not found";
            }
            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            return [
                "Estatus" => 0,
                "Mensaje" => "Data error".$e,
                "data" => "",
            ];
        }
    }

    public function show($id){
        $book = Book::with('authors','category','editorial')->find($id);
        $response = $this->getResponse();
        if ($book) {
            $response["error"] = false;
            $response["message"] =  "Successfull query--";
            $response["data"] = $book;

        }else{
            $response["message"] = "Data not found!!";
        }
        return $response;

    }

    public function delete($id){
        DB::beginTransaction();
        try {
            $book = Book::find($id);
            $response = $this -> getResponse();
            if ($book) {

                foreach($book->authors as $item){
                    $book->authors()->detach($item->id);
                }
                $book->delete();
                $response["error"] = false;
                $response["message"] = "Successfull delete";
                $response["data-id"] = $book->id;
                $response["data"] = $book;

            }else{
                $response["error"] = true;
                $response["message"] = "Data not found!!";
            }

            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            return [
                "Estatus" => 0,
                "Mensaje" => "Data error",
                "data" => "",
            ];
        }
    }

    public function addBookReview(Request $request){
        DB::beginTransaction();
        try {
            $book = new BookReview();
            $book->comment = trim($request->comment);
            $book->user_id = trim(auth()->user()->id);
            $book->book_id = trim($request->book_id);
            $book->save();
            $bookRes = BookReview::find($book->book_id);
            DB::commit();
            return $this->getResponse200($bookRes);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e->getMessage()]);
        }
    }

    public function updateBookReview(Request $request, $id){
        DB::beginTransaction();
        try {
            $book = BookReview::find($request->id);
            if($book->user_id != auth()->user()->id){
                return $this->getResponse403();
            }

            $book->comment = trim($request->comment);
            $book->edited = true;
            $book->user_id = trim(auth()->user()->id);
         //   $book->book_id = trim($request->book_id);
            $book->save();

            DB::commit();
            return $this->getResponse200($book);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e->getMessage()]);
        }
    }
}
