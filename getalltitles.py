import requests
import json
import re

# Function to sanitize filenames
def sanitize_filename(title):
    return re.sub(r'[\/:*?"<>|]', '_', title)

# Function to fetch titles from MediaWiki API
def fetch_all_titles(api_url):
    titles = []
    url = f"{api_url}?action=query&list=allpages&format=json&aplimit=max"

    while url:
        response = requests.get(url)
        data = response.json()
        pages = data['query']['allpages']
        titles.extend(page['title'] for page in pages)

        # Check for continuation
        if 'continue' in data:
            continue_param = data['continue']['apcontinue']
            url = f"{api_url}?action=query&list=allpages&format=json&aplimit=max&apcontinue={continue_param}"
        else:
            url = None

    return titles

# Main function
def main():
    api_url = "https://tgstation13.org/wiki/api.php"
    titles = fetch_all_titles(api_url)

    with open('titles.txt', 'w') as file:
        for title in titles:
            file.write(title + '\n')

if __name__ == "__main__":
    main()

