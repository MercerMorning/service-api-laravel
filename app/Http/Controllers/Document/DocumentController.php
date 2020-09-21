<?php

namespace App\Http\Controllers\Document;

use App\Models\Action;
use App\Models\Actors;
use App\Models\Meta;
use App\Models\Payload;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Crypt;

class DocumentController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     *
     * Создание
     */
    public function create()
    {
        $document = Document::create(['status' => 'draft']);
        return response($document->toArray());
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     *
     * Получение по id
     */
    public function get(string $id)
    {
        $document = Document::find((string)Crypt::decrypt($id));
        return response($document->toArray());
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Первое редактирование
     */
    public function update(string $id, Request $request)
    {
        $document = Document::find((string)Crypt::decrypt($id));
        if ($document->status == 'published' || $request->payload == null) {
            return response()->json(['error' => true], 400);
        }
        if ( is_null($document) ){
            return response()->json(['error' => true], 404);
        }
        $payloadFields = json_decode($request->payload);
        if (Document::find((string)Crypt::decrypt($id))->payload_id) {
            $payload = Payload::find((int)$document->payload_id);
            $payload->update(['actor' => (string)$payloadFields->actor]);
            $payload->hasMany('App\Models\Action')->delete();
            $payload->belongsTo('App\Models\Meta', 'meta_id')->delete();
            $meta = Meta::create(['type' => (string)$payloadFields->meta->type, 'color' => (string)$payloadFields->meta->color]);
            $payload->update(['meta_id' => (int)$meta->id]);
        } else {
            $meta = Meta::create(['type' => (string)$payloadFields->meta->type, 'color' => (string)$payloadFields->meta->color]);
            $payload = Payload::create(['actor' => (string)$payloadFields->actor, 'meta_id' => (int)$meta->id]);
            $document = Document::find(Crypt::decrypt($id));
            $document->update(['payload_id' => $payload->id]);
        }
        foreach ($payloadFields->actions as $action) {
            if (Actors::query()->where('name', '=', $action->actor)->count() == 0) {
                $actor = Actors::create(['name' => (string)$action->actor]);
            } else {
                $actor = Actors::query()->where('name', '=', (string)$action->actor)->first();
            }
            Action::create(['payload_id' => (int)$payload->id, 'name' => (string)$action->action, 'actor_id' => (int)$actor->id]);
        }
        return response($document->toArray());
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     *
     * Публикация
     */
    public function publish(string $id)
    {
        $document = new Document();
        $document = $document->find(Crypt::decrypt($id));
        if ( is_null($document) ){
            return response()->json(['error' => true], 404);
        }
        $document->update(['status' => 'published']);
        $document = $document;
        return response($document->toArray());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Вывод с постраничной навигацией
     */
    public function paginate (Request $request)
    {
        $documents = new Paginator(Document::all(), $request->perPage, $request->page);
        $pagination = [
            'pagination' => [
                "page" => (int)$documents->currentPage(),
                "perPage" => (int)$documents->perPage(),
                "total" => (int)Document::all()->count(),
            ]
        ];
        $documentList = ['document' => []];
        foreach ($documents as $document) {
            $documentList['document'][] = Document::find((int)$document->id)->toArray();
        }
        $documentList = array_reverse($documentList['document']);
        return $documentList;
        $result = array_merge($documentList, $pagination);
        return response($result);
    }
}
