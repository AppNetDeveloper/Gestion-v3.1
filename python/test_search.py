import sys
import os
import asyncio

# Add the scraping directory to the Python path
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

async def test_search():
    try:
        from scraping.search_engines import search_multiple_engines
        print("Successfully imported search_engines module!")
        
        # Test the search function
        print("Searching for 'test' on Google and DuckDuckGo...")
        results = await search_multiple_engines("test", ["google", "duckduckgo"], num_results=2)
        print("Search results:")
        for engine, urls in results.items():
            print(f"{engine}: {len(urls)} results")
            for i, url in enumerate(urls, 1):
                print(f"  {i}. {url}")
        
    except Exception as e:
        print(f"Error importing or using search_engines module: {e}")
        raise

if __name__ == "__main__":
    asyncio.run(test_search())
