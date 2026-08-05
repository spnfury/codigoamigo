from bs4 import BeautifulSoup
import json

def analyze_html(html_path):
    with open(html_path, 'r', encoding='utf-8') as f:
        html = f.read()
    soup = BeautifulSoup(html, 'html.parser')
    
    title = soup.title.string.strip() if soup.title else 'No Title'
    desc = soup.find('meta', attrs={'name': 'description'})
    desc = desc['content'].strip() if desc else 'No Description'
    
    h1 = [h.text.strip() for h in soup.find_all('h1')]
    h2 = [h.text.strip() for h in soup.find_all('h2')]
    h3 = [h.text.strip() for h in soup.find_all('h3')]
    
    text_len = len(soup.get_text())
    
    schemas = []
    for script in soup.find_all('script', type='application/ld+json'):
        try:
            schemas.append(json.loads(script.string))
        except:
            pass
            
    print(f"--- Analysis for {html_path} ---")
    print(f"Title: {title}")
    print(f"Description: {desc}")
    print(f"H1: {h1}")
    print(f"H2: {h2[:5]} ... (showing first 5)")
    print(f"H3: {h3[:5]} ... (showing first 5)")
    print(f"Text length: {text_len}")
    print(f"Schemas Found: {len(schemas)}")
    for i, s in enumerate(schemas):
        if isinstance(s, dict):
            print(f" Schema {i}: {s.get('@type')}")
        elif isinstance(s, list):
            print(f" Schema {i}: {[item.get('@type') for item in s if isinstance(item, dict)]}")
    
    print("\n")

analyze_html("ca.html")
