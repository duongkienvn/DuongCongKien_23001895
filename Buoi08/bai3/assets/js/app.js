// Câu 3: Vue 3 (Composition không bắt buộc; dùng Options API dễ học).
const { createApp } = Vue;

createApp({
    data() {
        return {
            products: [],
            pagination: { page: 1, limit: 5, total: 0, total_pages: 1 },
            loading: false,
            loadFailed: false,
            busy: false,
            editingId: null,
            selectedImage: null,
            selectedImageUrl: '',
            existingImage: null,
            imageError: '',
            pendingUpload: null,
            form: { name: '', price: '', stock: 0 },
            message: { type: '', text: '' },
        };
    },

    computed: {
        isEditing() {
            return this.editingId !== null;
        },
        imagePreview() {
            return this.selectedImageUrl || this.imageUrl(this.existingImage);
        },
        inStockCount() {
            return this.products.filter(product => product.stock > 0).length;
        },
        outOfStockCount() {
            return this.products.filter(product => product.stock === 0).length;
        },
    },

    methods: {
        showMessage(type, text) {
            this.message = { type, text };
        },
        clearMessage() {
            this.message = { type: '', text: '' };
        },
        formatPrice(price) {
            return Number(price).toLocaleString('vi-VN') + ' đ';
        },
        resetForm() {
            this.clearSelectedImage();
            this.editingId = null;
            this.existingImage = null;
            this.pendingUpload = null;
            this.form = { name: '', price: '', stock: 0 };
        },
        imageUrl(image) {
            return typeof image === 'string' && /^uploads\/[a-zA-Z0-9_-]+\.(?:png|jpe?g)$/i.test(image)
                ? '../bai2/' + image : '';
        },
        clearSelectedImage(resetInput = true) {
            if (this.selectedImageUrl) URL.revokeObjectURL(this.selectedImageUrl);
            this.selectedImage = null;
            this.selectedImageUrl = '';
            this.imageError = '';
            if (resetInput && this.$refs?.imageInput) this.$refs.imageInput.value = '';
        },
        selectImage(event) {
            const file = event.target.files[0];
            this.clearSelectedImage(false);
            if (!file) return;
            if (!/\.(?:jpe?g|png)$/i.test(file.name) || !['image/jpeg', 'image/png'].includes(file.type)) {
                this.imageError = 'Chỉ chọn ảnh JPG, JPEG hoặc PNG.';
            } else if (file.size === 0 || file.size > 2 * 1024 * 1024) {
                this.imageError = 'Ảnh phải có dung lượng lớn hơn 0 và không quá 2 MB.';
            }
            if (this.imageError) {
                event.target.value = '';
                return;
            }
            this.selectedImage = file;
            this.selectedImageUrl = URL.createObjectURL(file);
        },
        async uploadImage(productId) {
            const data = new FormData();
            data.append('product_id', String(productId));
            data.append('image', this.selectedImage);
            return this.request('../bai2/api/upload.php', { method: 'POST', body: data });
        },
        async finishUpload() {
            const pending = this.pendingUpload;
            try {
                await this.uploadImage(pending.productId);
            } catch (error) {
                await this.loadProducts(pending.reloadPage);
                this.showMessage('error', 'Đã lưu thông tin sản phẩm #' + pending.productId
                    + ', nhưng tải ảnh thất bại: ' + error.message + ' Chọn Thử tải ảnh lại để tiếp tục.');
                return;
            }
            this.resetForm();
            const refreshed = await this.loadProducts(pending.reloadPage);
            this.showMessage(refreshed ? 'success' : 'error', refreshed
                ? 'Đã lưu sản phẩm và tải ảnh thành công!'
                : 'Đã lưu sản phẩm và ảnh, nhưng chưa tải lại được danh sách. Nhấn Làm mới để kiểm tra dữ liệu.');
        },
        async retryUpload() {
            if (this.busy || this.loading || !this.pendingUpload || !this.selectedImage || this.imageError) return;
            this.busy = true;
            this.clearMessage();
            try {
                await this.finishUpload();
            } finally {
                this.busy = false;
            }
        },

        // Gửi HTTP request; nếu API báo lỗi thì ném message để hiển thị.
        async request(url, options = {}) {
            const mutating = options.method && options.method !== 'GET';
            let response;
            try {
                response = await fetch(url, {
                    ...options,
                    headers: { Accept: 'application/json', ...options.headers },
                });
            } catch (cause) {
                const error = new Error('Không kết nối được máy chủ.');
                error.outcomeUnknown = !!mutating;
                throw error;
            }
            const contentType = response.headers.get('Content-Type') || 'không có Content-Type';
            const invalidResponse = () => {
                const error = new Error(`Máy chủ không trả về JSON hợp lệ theo API (HTTP ${response.status}; ${contentType}). Hãy kiểm tra đường dẫn API và máy chủ PHP.`);
                error.outcomeUnknown = !!mutating;
                return error;
            };
            if (!/^application\/json(?:\s*;|$)/i.test(contentType)) {
                throw invalidResponse();
            }
            let result;
            try {
                result = await response.json();
            } catch (error) {
                throw invalidResponse();
            }
            if (!result || typeof result !== 'object' || Array.isArray(result) || typeof result.success !== 'boolean') {
                throw invalidResponse();
            }

            if (!response.ok || result.success !== true) {
                const error = new Error(typeof result.message === 'string' && result.message.trim()
                    ? result.message : `Có lỗi xảy ra khi gọi API (HTTP ${response.status}).`);
                error.status = response.status;
                throw error;
            }
            return result;
        },

        // GET: gọi lại mỗi khi mới mở trang, hoặc sau POST / PUT / DELETE.
        async loadProducts(page = this.pagination.page) {
            if (this.loading) return false;
            this.loading = true;
            this.clearMessage();
            try {
                const result = await this.request(`../bai2/api/products.php?page=${page}&limit=5`);
                const ids = new Set();
                if (!Array.isArray(result.data) || !result.data.every(product => {
                    if (!product || !Number.isInteger(product.id) || product.id < 1 || product.id > 2147483647 || ids.has(product.id)
                        || typeof product.name !== 'string' || !product.name.trim() || [...product.name].length > 100
                        || !Number.isInteger(product.price) || product.price < 1 || product.price > 2147483647
                        || !Number.isInteger(product.stock) || product.stock < 0 || product.stock > 2147483647
                        || (product.image !== null && !this.imageUrl(product.image))) return false;
                    ids.add(product.id);
                    return true;
                })) {
                    throw new Error('Danh sách sản phẩm trong phản hồi API không hợp lệ.');
                }
                const pagination = result.pagination;
                if (!pagination || pagination.limit !== 5 || !Number.isInteger(pagination.page) || pagination.page < 1
                    || !Number.isInteger(pagination.total) || pagination.total < 0
                    || pagination.total_pages !== Math.max(1, Math.ceil(pagination.total / 5))
                    || pagination.page > pagination.total_pages
                    || result.data.length !== Math.min(5, pagination.total - (pagination.page - 1) * 5)) {
                    throw new Error('Thông tin phân trang trong phản hồi API không hợp lệ.');
                }
                this.products = result.data;
                this.pagination = pagination;
                this.loadFailed = false;
                return true;
            } catch (error) {
                this.loadFailed = true;
                this.showMessage('error', 'Không tải được danh sách: ' + error.message);
                return false;
            } finally {
                this.loading = false;
            }
        },

        editProduct(product) {
            if (this.busy || this.loading || this.loadFailed || this.pendingUpload) return;
            this.clearSelectedImage();
            this.editingId = product.id;
            this.existingImage = product.image;
            this.form = {
                name: product.name,
                price: product.price,
                stock: product.stock,
            };
            this.clearMessage();
            document.getElementById('product-name').focus();
        },

        // Tạo/sửa dùng chung một form; server vẫn kiểm tra dữ liệu lần nữa.
        async saveProduct() {
            if (this.busy || this.loading || this.loadFailed) return;
            if (this.pendingUpload) return this.retryUpload();
            if (this.imageError) return;
            const name = typeof this.form.name === 'string' ? this.form.name.trim() : '';
            const price = this.form.price;
            const stock = this.form.stock;

            if (name.length < 1 || [...name].length > 100) {
                this.showMessage('error', 'Tên sản phẩm phải có từ 1 đến 100 ký tự.');
                return;
            }
            if (!Number.isInteger(price) || price <= 0 || price > 2147483647) {
                this.showMessage('error', 'Đơn giá phải là số nguyên lớn hơn 0.');
                return;
            }
            if (!Number.isInteger(stock) || stock < 0 || stock > 2147483647) {
                this.showMessage('error', 'Số lượng tồn phải là số nguyên không âm.');
                return;
            }

            const isEdit = this.isEditing;
            const url = isEdit ? `../bai2/api/products.php?id=${this.editingId}` : '../bai2/api/products.php';
            this.busy = true;
            this.clearMessage();

            try {
                const saved = await this.request(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, price, stock }),
                });
                const reloadPage = isEdit ? this.pagination.page : 1;
                if (this.selectedImage) {
                    const productId = isEdit ? this.editingId : saved.data?.id;
                    if (!Number.isInteger(productId) || productId < 1) {
                        const error = new Error('Không nhận được ID sản phẩm vừa tạo.');
                        error.outcomeUnknown = true;
                        throw error;
                    }
                    this.editingId = productId;
                    this.pendingUpload = { productId, reloadPage };
                    await this.finishUpload();
                    return;
                }
                this.resetForm();
                const refreshed = await this.loadProducts(reloadPage);
                if (refreshed) {
                    this.showMessage('success', isEdit ? 'Cập nhật sản phẩm thành công!' : 'Thêm sản phẩm thành công!');
                } else {
                    this.showMessage('error', (isEdit ? 'Đã cập nhật' : 'Đã thêm') + ' sản phẩm, nhưng chưa tải lại được danh sách. Nhấn Làm mới để kiểm tra dữ liệu.');
                }
            } catch (error) {
                if (error.outcomeUnknown || error.status === 404) await this.loadProducts();
                this.showMessage('error', error.outcomeUnknown
                    ? 'Chưa xác nhận được kết quả lưu. ' + error.message + ' Hãy kiểm tra danh sách trước khi gửi lại để tránh thêm trùng.'
                    : error.message);
            } finally {
                this.busy = false;
            }
        },

        // DELETE: chỉ gửi request khi người dùng xác nhận.
        async deleteProduct(product) {
            if (this.busy || this.loading || this.loadFailed) return;
            if (!window.confirm(`Bạn có chắc muốn xóa sản phẩm "${product.name}"?`)) {
                return;
            }
            this.busy = true;
            this.clearMessage();
            try {
                await this.request(`../bai2/api/products.php?id=${product.id}`, { method: 'DELETE' });
                if (this.editingId === product.id) {
                    this.resetForm();
                }
                const refreshed = await this.loadProducts();
                if (refreshed) {
                    this.showMessage('success', 'Xóa sản phẩm thành công!');
                } else {
                    this.showMessage('error', 'Đã xóa sản phẩm, nhưng chưa tải lại được danh sách. Nhấn Làm mới để kiểm tra dữ liệu.');
                }
            } catch (error) {
                if (error.outcomeUnknown || error.status === 404) await this.loadProducts();
                this.showMessage('error', error.outcomeUnknown
                    ? 'Chưa xác nhận được kết quả xóa. ' + error.message + ' Hãy kiểm tra danh sách trước khi thử lại.'
                    : error.message);
            } finally {
                this.busy = false;
            }
        },
    },

    mounted() {
        this.loadProducts();
    },
    beforeUnmount() {
        if (this.selectedImageUrl) URL.revokeObjectURL(this.selectedImageUrl);
    },
}).mount('#app');
