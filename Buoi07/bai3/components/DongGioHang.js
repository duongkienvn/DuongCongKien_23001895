// BÀI 3 - Component con: CHỈ đọc props và gửi sự kiện lên component cha.
window.DongGioHang = {
  props: {
    id: { type: Number, required: true },
    ten: { type: String, required: true },
    donGia: { type: Number, required: true },
    soLuong: { type: Number, required: true }
  },

  // Khai báo hai sự kiện tùy chỉnh theo đúng tên trong đề.
  emits: ['doi-so-luong', 'yeu-cau-xoa'],

  computed: {
    thanhTien() {
      return this.donGia * this.soLuong;
    }
  },

  methods: {
    dinhDangTien(soTien) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency', currency: 'VND', maximumFractionDigits: 0
      }).format(soTien);
    },
    tang() {
      if (this.soLuong < 99) {
        // Đối số sự kiện: mã sản phẩm, số lượng MỚI.
        this.$emit('doi-so-luong', this.id, this.soLuong + 1);
      }
    },
    giam() {
      if (this.soLuong > 1) {
        this.$emit('doi-so-luong', this.id, this.soLuong - 1);
      }
    },
    xoa() {
      this.$emit('yeu-cau-xoa', this.id);
    }
  },

  template: `
    <article class="cart-item">
      <div class="cart-product">
        <span class="product-avatar" aria-hidden="true">{{ ten.charAt(0) }}</span>
        <div>
          <h3>{{ ten }}</h3>
          <p class="muted">Đơn giá: {{ dinhDangTien(donGia) }}</p>
        </div>
      </div>
      <div class="quantity-control" role="group" :aria-label="'Điều chỉnh số lượng ' + ten">
        <button type="button" @click="giam" :disabled="soLuong <= 1" :aria-label="'Giảm số lượng ' + ten">−</button>
        <output>{{ soLuong }}</output>
        <button type="button" @click="tang" :disabled="soLuong >= 99" :aria-label="'Tăng số lượng ' + ten">+</button>
      </div>
      <strong class="item-total">{{ dinhDangTien(thanhTien) }}</strong>
      <button type="button" class="btn btn-danger btn-small" @click="xoa" :aria-label="'Xóa ' + ten">Xóa</button>
    </article>
  `
};
