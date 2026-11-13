// Node built-ins only. APP_JS_PATH allows checking a saved baseline too.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const code = fs.readFileSync(process.env.APP_JS_PATH || path.join(__dirname, '../../bai3/assets/js/app.js'), 'utf8');
const seed = () => [
    { id: 1, name: 'Bút bi', price: 5000, stock: 20 },
    { id: 2, name: 'Vở ô ly', price: 12000, stock: 15 },
    { id: 3, name: 'Thước kẻ', price: 8000, stock: 0 },
];
function response(status, payload, type = 'application/json; charset=utf-8', raw = false) {
    return { status, ok: status >= 200 && status < 300, headers: { get: () => type },
        json: async () => raw ? JSON.parse(payload) : payload };
}
function fixture() {
    const f = { rows: seed(), calls: [], allowDelete: false, nextId: 4, intercept: null, confirms: 0 };
    let options;
    const normalFetch = async (url, init = {}) => {
        const method = init.method || 'GET';
        const id = Number(new URL(url, 'http://localhost/bai3/').searchParams.get('id'));
        if (method === 'GET') return response(200, { success: true, data: f.rows.map(row => ({ ...row })) });
        if (method === 'POST') {
            const item = { id: f.nextId++, ...JSON.parse(init.body) };
            f.rows.push(item);
            return response(201, { success: true, data: { id: item.id } });
        }
        const index = f.rows.findIndex(row => row.id === id);
        if (index < 0) return response(404, { success: false, message: 'Không tìm thấy sản phẩm.' });
        if (method === 'PUT') Object.assign(f.rows[index], JSON.parse(init.body));
        if (method === 'DELETE') f.rows.splice(index, 1);
        return response(200, { success: true });
    };
    vm.runInNewContext(code, {
        Vue: { createApp: value => { options = value; return { mount() {} }; } },
        fetch: async (url, init = {}) => {
            f.calls.push({ url, ...init, method: init.method || 'GET' });
            return f.intercept ? f.intercept(url, init, normalFetch) : normalFetch(url, init);
        },
        window: { confirm: () => { f.confirms++; return f.allowDelete; } },
        document: { getElementById: () => ({ focus() {} }) }, console,
    });
    f.app = options.data();
    for (const [name, fn] of Object.entries(options.methods)) f.app[name] = fn.bind(f.app);
    for (const [name, fn] of Object.entries(options.computed)) Object.defineProperty(f.app, name, { get: () => fn.call(f.app) });
    f.mount = () => options.mounted.call(f.app);
    return f;
}
let passed = 0, failed = 0;
async function test(label, fn) {
    try { await fn(); passed++; console.log('[PASS] ' + label); }
    catch (error) { failed++; console.error('[FAIL] ' + label + ': ' + error.message); }
}
async function main() {
    await test('mounted GET and stock counters', async () => {
        const f = fixture(); f.mount(); await new Promise(setImmediate);
        assert.equal(f.app.products.length, 3); assert.equal(f.app.inStockCount, 2); assert.equal(f.app.outOfStockCount, 1);
        assert.equal(f.app.loading, false);
    });
    await test('POST -> GET, trim, persisted data, reset form', async () => {
        const f = fixture(); f.app.form = { name: '  Bút chì  ', price: 3000, stock: 12 };
        await f.app.saveProduct();
        assert.equal(f.rows[3].name, 'Bút chì'); assert.equal(f.app.products.length, 4);
        assert.equal(f.app.form.name, ''); assert.equal(f.app.editingId, null); assert.equal(f.app.busy, false);
        assert.equal(f.app.message.type, 'success'); assert.equal(f.calls[0].method, 'POST'); assert.equal(f.calls[1].method, 'GET');
        assert.equal(f.calls[0].headers['Content-Type'], 'application/json');
    });
    await test('PUT uses selected ID, refreshes, resets edit mode', async () => {
        const f = fixture(); await f.app.loadProducts(); f.app.editProduct(f.app.products[0]);
        f.app.form.price = 9500; await f.app.saveProduct();
        assert.equal(f.app.products[0].price, 9500); assert.equal(f.app.isEditing, false);
        assert.equal(f.calls.at(-2).method, 'PUT'); assert.equal(f.calls.at(-2).url, '../bai2/api/products.php?id=1');
        assert.equal(f.calls.at(-1).method, 'GET');
    });
    await test('Cancel DELETE sends no request; confirmed DELETE resets selected form', async () => {
        const f = fixture(); await f.app.loadProducts(); const product = f.app.products[0];
        await f.app.deleteProduct(product); assert.equal(f.calls.length, 1);
        f.app.editProduct(product); f.allowDelete = true; await f.app.deleteProduct(product);
        assert.equal(f.app.products.length, 2); assert.equal(f.app.isEditing, false);
        assert.equal(f.calls.at(-2).method, 'DELETE'); assert.equal(f.calls.at(-1).method, 'GET');
    });
    await test('Frontend rejects invalid name, price, stock without HTTP', async () => {
        const f = fixture();
        for (const changes of [{name:' '},{name:'\u00a0'},{name:'😀'.repeat(101)},{price:''},{price:0},{price:1.5},{price:'1'},{price:2147483648},{stock:''},{stock:-1},{stock:0.5},{stock:2147483648}]) {
            f.app.form = { name: 'A', price: 1, stock: 0, ...changes }; await f.app.saveProduct();
            assert.equal(f.app.message.type, 'error');
        }
        assert.equal(f.calls.length, 0);
    });
    await test('100 Unicode code points accepted', async () => {
        const f = fixture(); f.app.form = {name:'😀'.repeat(100),price:1,stock:0}; await f.app.saveProduct();
        assert.equal(f.app.message.type, 'success');
    });
    for (const [label, reply] of [
        ['HTML 404',response(404,'<h1>Not found</h1>','text/html',true)],
        ['invalid JSON 500',response(500,'<br>Fatal error','application/json',true)],
        ['empty 204',response(204,'','application/json',true)],
        ['JSON null',response(200,null)], ['JSON array',response(200,[])], ['missing success',response(200,{})],
        ['wrong success type',response(200,{success:'true'})], ['wrong MIME',response(200,{success:true},'text/plain')],
    ]) await test('Reject ' + label + ' with useful controlled message', async () => {
        const f = fixture(); f.intercept = async () => reply;
        await assert.rejects(f.app.request('../bai2/api/products.php'), error => /JSON hợp lệ/.test(error.message) && /HTTP/.test(error.message));
    });
    for (const data of [undefined,null,{},[null],[{id:1}], [{id:1,name:'A',price:'1',stock:0}], [seed()[0],seed()[0]]]) {
        await test('Reject invalid product list ' + JSON.stringify(data), async () => {
            const f = fixture(); f.intercept = async () => response(200,{success:true,data});
            assert.equal(await f.app.loadProducts(), false); assert.equal(f.app.loadFailed, true);
            assert.equal(Array.isArray(f.app.products), true); assert.equal(f.app.loading, false);
        });
    }
    await test('HTTP 400/404/500, success:false and non-string message never become success', async () => {
        const f = fixture();
        for (const [status,payload] of [[400,{success:false,message:'Dữ liệu sai'}],[404,{success:false,message:'Không tìm thấy'}],[500,{success:true}],[200,{success:false,message:{html:'bad'}}]]) {
            f.intercept = async () => response(status,payload);
            await assert.rejects(f.app.request('api'), error => typeof error.message === 'string' && error.message !== '[object Object]');
        }
    });
    await test('GET failure then retry clears error and unblocks operations', async () => {
        const f = fixture(); f.intercept = async () => { throw new Error('offline'); };
        assert.equal(await f.app.loadProducts(), false); assert.equal(f.app.loadFailed, true);
        f.intercept = null; assert.equal(await f.app.loadProducts(), true);
        assert.equal(f.app.loadFailed, false); assert.equal(f.app.message.text, '');
    });
    await test('Loading GET blocks overlapping GET/save/delete/edit', async () => {
        const f = fixture(); let release;
        f.intercept = (url, init, normal) => f.calls.length === 1
            ? new Promise(resolve => { release = resolve; }) : normal(url, init);
        const pending = f.app.loadProducts(); assert.equal(f.app.loading, true);
        f.app.form = {name:'A',price:1,stock:0};
        await f.app.loadProducts(); await f.app.saveProduct(); f.allowDelete=true;
        await f.app.deleteProduct(seed()[0]); f.app.editProduct(seed()[0]);
        assert.equal(f.calls.length,1); assert.equal(f.confirms,0); assert.equal(f.app.editingId,null);
        release(response(200,{success:true,data:seed()})); await pending;
        assert.equal(f.app.loading,false);
    });
    await test('Busy mutation blocks duplicate POST and DELETE', async () => {
        const f = fixture(); let release;
        f.intercept = (url,init,normal) => f.calls.length === 1 ? new Promise(resolve => { release = () => normal(url,init).then(resolve); }) : normal(url,init);
        f.app.form={name:'A',price:1,stock:0}; const pending=f.app.saveProduct();
        await f.app.saveProduct(); f.allowDelete=true; await f.app.deleteProduct(seed()[0]);
        assert.equal(f.calls.length,1); release(); await pending; assert.equal(f.rows.length,4); assert.equal(f.app.busy,false);
    });
    for (const method of ['POST','PUT','DELETE']) await test(method+' succeeds but refresh fails: report committed change and hide stale table', async () => {
        const f=fixture(); await f.app.loadProducts();
        if(method==='PUT') f.app.editProduct(f.app.products[0]); else f.app.form={name:'A',price:1,stock:0};
        f.intercept=(url,init,normal)=> !init.method ? Promise.resolve(response(500,{success:false,message:'DB error'})) : normal(url,init);
        f.allowDelete=true;
        if(method==='DELETE') await f.app.deleteProduct(f.app.products[0]); else await f.app.saveProduct();
        assert.match(f.app.message.text,/Đã (thêm|cập nhật|xóa)/); assert.equal(f.app.message.type,'error');
        assert.equal(f.app.loadFailed,true); assert.equal(f.app.busy,false);
        assert.equal(f.rows.length,method==='POST'?4:method==='DELETE'?2:3);
    });
    for (const method of ['POST','DELETE']) await test(method+' committed but HTML reply: refresh once and report uncertain outcome',async()=>{
        const f=fixture(); await f.app.loadProducts(); f.allowDelete=true; f.app.form={name:'A',price:1,stock:0};
        f.intercept=async(url,init,normal)=> {const result=await normal(url,init);return init.method===method?response(200,'<h1>Proxy</h1>','text/html',true):result;};
        if(method==='DELETE') await f.app.deleteProduct(f.app.products[0]); else await f.app.saveProduct();
        assert.match(f.app.message.text,/Chưa xác nhận/); assert.equal(f.app.message.type,'error'); assert.equal(f.app.busy,false);
        assert.equal(f.app.products.length,method==='POST'?4:2); assert.equal(f.calls.filter(c=>c.method===method).length,1);
    });
    await test('DELETE missing row returns error then reconciles list',async()=>{
        const f=fixture(); await f.app.loadProducts(); const row=f.app.products[0]; f.rows.shift();f.allowDelete=true;
        await f.app.deleteProduct(row); assert.match(f.app.message.text,/Không tìm thấy/);assert.equal(f.app.products.length,2);
    });
    await test('Network mutation failure releases busy and reports uncertainty',async()=>{
        const f=fixture(); f.app.form={name:'A',price:1,stock:0}; f.intercept=async()=>{throw new Error('offline');};
        await f.app.saveProduct();assert.match(f.app.message.text,/Chưa xác nhận/);assert.equal(f.app.busy,false);assert.equal(f.app.loading,false);
    });
    console.log(`RESULT: ${passed} PASS, ${failed} FAIL`); process.exitCode=failed?1:0;
}
main().catch(error=>{console.error(error);process.exitCode=1;});
