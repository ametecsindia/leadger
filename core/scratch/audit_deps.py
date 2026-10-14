import json, os

with open('composer.json') as f:
    c = json.load(f)

reqs = list(c.get('require', {}).keys()) + list(c.get('require-dev', {}).keys())

print(f"{'Package':<35} {'Installed':<12} {'Illuminate/Laravel/Symfony Constraints'}")
print("-" * 80)
for p in reqs:
    if p == 'php':
        continue
    vp = os.path.join('vendor', p, 'composer.json')
    if os.path.exists(vp):
        with open(vp, encoding='utf-8') as vf:
            vdata = json.load(vf)
            vreq = vdata.get('require', {})
            ill = {k: v for k, v in vreq.items() if 'illuminate' in k or 'laravel' in k or 'symfony' in k or 'phpunit' in k}
            ver = vdata.get('version', 'N/A')
            print(f"{p:<35} {ver:<12} {ill}")
    else:
        print(f"{p:<35} {'NOT INSTALLED':<12}")
