import urllib.request
from bs4 import BeautifulSoup
import json
import ssl

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def fetch_and_analyze(url):
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    html = urllib.request.urlopen(req, context=ctx).read()
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
            
    print(f"--- Analysis for {url} ---")
    print(f"Title: {title}")
    print(f"Description: {desc}")
    print(f"H1: {h1}")
    print(f"H2: {h2[:5]} ... (showing first 5)")
    print(f"H3: {h3[:5]} ... (showing first 5)")
    print(f"Text length: {text_len}")
    print(f"Schemas Found: {len(schemas)}")
    for i, s in enumerate(schemas):
        print(f" Schema {i}: {s.get('@type') if isinstance(s, dict) else [item.get('@type') for item in s if isinstance(item, dict)]}")
    
    print("\n")

fetch_and_analyze("https://referity.es/promociones/5-euros-gratis-para-tu-primer-trayecto-en-moto-con-yego")
fetch_and_analyze("https://www.codigoamigo.com/de-yego")
