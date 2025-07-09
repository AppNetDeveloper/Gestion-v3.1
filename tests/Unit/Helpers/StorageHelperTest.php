<?php

namespace Tests\Unit\Helpers;

use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;

class StorageHelperTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        
        // Configurar mocks para Storage y Log
        Storage::fake('local');
        Log::shouldReceive('warning')->andReturn(true);
    }
    
    /** @test */
    public function it_returns_false_when_path_is_null_in_exists()
    {
        $this->assertFalse(StorageHelper::exists(null));
    }
    
    /** @test */
    public function it_returns_false_when_path_is_empty_in_exists()
    {
        $this->assertFalse(StorageHelper::exists(''));
    }
    
    /** @test */
    public function it_returns_true_when_file_exists()
    {
        Storage::put('test.txt', 'Contenido de prueba');
        
        $this->assertTrue(StorageHelper::exists('test.txt'));
    }
    
    /** @test */
    public function it_returns_false_when_file_does_not_exist()
    {
        $this->assertFalse(StorageHelper::exists('archivo_inexistente.txt'));
    }
    
    /** @test */
    public function it_returns_false_when_path_is_null_in_delete()
    {
        $this->assertFalse(StorageHelper::delete(null));
    }
    
    /** @test */
    public function it_returns_false_when_file_does_not_exist_in_delete()
    {
        $this->assertFalse(StorageHelper::delete('archivo_inexistente.txt'));
    }
    
    /** @test */
    public function it_returns_true_when_file_is_deleted()
    {
        Storage::put('test_delete.txt', 'Contenido de prueba');
        
        $this->assertTrue(StorageHelper::delete('test_delete.txt'));
        $this->assertFalse(Storage::exists('test_delete.txt'));
    }
    
    /** @test */
    public function it_aborts_with_404_when_path_is_null_in_download()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        
        StorageHelper::download(null);
    }
    
    /** @test */
    public function it_aborts_with_404_when_file_does_not_exist_in_download()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        
        StorageHelper::download('archivo_inexistente.txt');
    }
}
