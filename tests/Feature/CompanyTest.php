<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyToken;
use App\Models\Product;
use App\Models\User;
use App\Utils\Traits\MakesHash;
use Faker\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * @test
 * @covers App\Http\Controllers\CompanyController
 */
class CompanyTest extends TestCase
{
    use MakesHash;

    use DatabaseTransactions;

    public function setUp() :void
    {
        parent::setUp();

        Session::start();

        $this->faker = \Faker\Factory::create();

        Model::reguard();

    }

    public function testCompanyList()
    {

        $data = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
             'name' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'ALongAndBrilliantPassword123',
            '_token' => csrf_token(),
            'privacy_policy' => 1,
            'terms_of_service' => 1
        ];


        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
            ])->post('/api/v1/signup', $data);


        $response->assertStatus(200);

        $acc = $response->json();

        $account = Account::find($this->decodePrimaryKey($acc['data'][0]['account']['id']));        

        $token = $account->default_company->tokens->first()->token;

        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $token,
            ])->get('/api/v1/companies');

        $response->assertStatus(200);


        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $token,
        ])->post('/api/v1/companies/', 
            [
                'name' => 'A New Company',
                'logo' => UploadedFile::fake()->image('avatar.jpg')
            ]
        )
        ->assertStatus(200)->decodeResponseJson();
\Log::error($response['data'][0]);
        //$company = Company::find($this->decodePrimaryKey($response['data']['company_users'][0]['company']['id']));        
        $company = Company::find($this->decodePrimaryKey($response['data'][0]['company']['id']));        

        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $token,
        ])->post('/api/v1/companies/', 
            [
                'name' => 'A New Company',
                'logo' => UploadedFile::fake()->create('avatar.pdf',100)
            ]
        )
        ->assertStatus(302);

      //  Log::error($company);

        $token = CompanyToken::whereCompanyId($company->id)->first()->token;

        $company_update = [
            'name' => 'CHANGE NAME',
         //   'logo' => UploadedFile::fake()->image('avatar.jpg')
        ];

        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $token,
            ])->put('/api/v1/companies/'.$this->encodePrimaryKey($company->id), $company_update)
            ->assertStatus(200);



        $response = $this->withHeaders([
            'X-API-SECRET' => config('ninja.api_secret'),
            'X-API-TOKEN' => $token,
        ])->delete('/api/v1/companies/'.$this->encodePrimaryKey($company->id))
        ->assertStatus(200);
    }
}
