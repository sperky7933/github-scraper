import requests
import json
import os
import xml.etree.ElementTree as ET
import re

# Define directories
pages_dir = 'pages'
revisions_dir = 'revisions'
images_dir = 'images'

os.makedirs(pages_dir, exist_ok=True)
os.makedirs(revisions_dir, exist_ok=True)
os.makedirs(images_dir, exist_ok=True)

def sanitize_filename(title):
    # Replace characters that are invalid in filenames
    return re.sub(r'[\/:*?"<>|]', '_', title)

def fetch_page_content(title):
    url = f"https://tgstation13.org/wiki/api.php?action=parse&page={title}&format=json"
    response = requests.get(url)
    return response.json()

def fetch_revisions(title):
    url = f"https://tgstation13.org/wiki/api.php?action=query&prop=revisions&rvprop=content|timestamp|user&format=json&titles={title}&rvlimit=max"
    response = requests.get(url)
    return response.json()

def fetch_images():
    url = "https://tgstation13.org/wiki/api.php?action=query&list=allimages&format=json&ailimit=max"
    response = requests.get(url)
    return response.json()

def download_image(url, filename):
    response = requests.get(url)
    with open(os.path.join(images_dir, filename), 'wb') as file:
        file.write(response.content)

def fetch_all_pages():
    url = "https://tgstation13.org/wiki/api.php?action=query&list=allpages&format=json&aplimit=max"
    response = requests.get(url)
    data = response.json()
    return [page['title'] for page in data['query']['allpages']]

def download_pages_and_revisions(titles):
    for title in titles:
        sanitized_title = sanitize_filename(title)

        # Check if page content exists
        page_path = os.path.join(pages_dir, f"{sanitized_title}.json")
        revision_path = os.path.join(revisions_dir, f"{sanitized_title}_revisions.json")

        if os.path.exists(page_path):
            print(f"Skipping page: {sanitized_title} (already exists)")
        else:
            print(f"Fetching page: {sanitized_title}")
            page_content = fetch_page_content(title)
            with open(page_path, 'w') as file:
                json.dump(page_content, file, indent=4)

        if os.path.exists(revision_path):
            print(f"Skipping revisions: {sanitized_title} (already exists)")
        else:
            print(f"Fetching revisions: {sanitized_title}")
            revisions_data = fetch_revisions(title)
            with open(revision_path, 'w') as file:
                json.dump(revisions_data, file, indent=4)

def download_all_images():
    images_data = fetch_images()
    images = [image['url'] for image in images_data['query']['allimages']]
    
    for image_url in images:
        img_name = sanitize_filename(image_url.split('/')[-1])
        image_path = os.path.join(images_dir, img_name)

        if os.path.exists(image_path):
            print(f"Skipping image: {img_name} (already exists)")
        else:
            print(f"Downloading image: {img_name}")
            download_image(image_url, img_name)

def create_xml_dump():
    root = ET.Element("MediaWikiDump")

    # Add pages
    pages_element = ET.SubElement(root, "Pages")
    for filename in os.listdir(pages_dir):
        if filename.endswith('.json'):
            page_title = filename.replace('.json', '')
            page_element = ET.SubElement(pages_element, "Page", title=page_title)
            with open(os.path.join(pages_dir, filename), 'r') as file:
                content = json.load(file)
                content_element = ET.SubElement(page_element, "Content")
                content_element.text = json.dumps(content)  # Simplified, actual content extraction needed

    # Add revisions
    revisions_element = ET.SubElement(root, "Revisions")
    for filename in os.listdir(revisions_dir):
        if filename.endswith('_revisions.json'):
            page_title = filename.replace('_revisions.json', '')
            revision_element = ET.SubElement(revisions_element, "Revision", title=page_title)
            with open(os.path.join(revisions_dir, filename), 'r') as file:
                content = json.load(file)
                content_element = ET.SubElement(revision_element, "Content")
                content_element.text = json.dumps(content)  # Simplified, actual content extraction needed

    # Add images
    images_element = ET.SubElement(root, "Images")
    for filename in os.listdir(images_dir):
        if filename:
            image_element = ET.SubElement(images_element, "Image", name=filename)
            image_element.text = f"/images/{filename}"  # Adjust path if necessary

    tree = ET.ElementTree(root)
    tree.write("combined_dump.xml")

if __name__ == "__main__":
    titles = fetch_all_pages()
    download_pages_and_revisions(titles)
    download_all_images()
    create_xml_dump()