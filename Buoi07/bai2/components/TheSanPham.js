window.TheSanPham = {
  props: {
    ten: { type: String, required: true },
    gia: { type: Number, required: true },
    anh: { type: String, default: 'assets/mac-dinh.svg' },
    moTa: { type: String, default: 'Chưa có mô tả cho sản phẩm này.' },
    soLuongTon: { type: Number, default: 0 }
  },

  emits: ['mua'],

  data() {
    return {
      hienMoTa: false
    };
  },

  computed: {
    conHang() {
      return this.soLuongTon > 0;
    }
  },

  methods: {
    dinhDangTien(soTien) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency', currency: 'VND', maximumFractionDigits: 0
      }).format(soTien);
    },
    batTatMoTa() {
      this.hienMoTa = !this.hienMoTa;
    },
    muaSanPham() {
      if (!this.conHang) return;
      this.$emit('mua', this.ten);
    }
  },

  template: `
    <article class="product-card">
      <div class="product-visual">
        <img :src="anh" :alt="'Hình minh họa ' + ten" width="360" height="220">
      </div>
      <div class="product-body">
        <div class="product-heading">
          <h2>{{ ten }}</h2>
          <span class="badge" :class="conHang ? 'badge-good' : 'badge-bad'">
            {{ conHang ? 'Còn hàng' : 'Hết hàng' }}
          </span>
        </div>
        <p class="product-price">{{ dinhDangTien(gia) }}</p>
        <p class="stock-note">Tồn kho: <strong>{{ soLuongTon }}</strong></p>
        <p v-if="hienMoTa" class="product-description">{{ moTa }}</p>
        <div class="product-actions">
          <button class="btn btn-secondary" type="button" @click="batTatMoTa"
                  :aria-expanded="hienMoTa">
            {{ hienMoTa ? 'Ẩn mô tả' : 'Xem mô tả' }}
          </button>
          <button class="btn" type="button" :disabled="!conHang" @click="muaSanPham">Mua</button>
        </div>
      </div>
    </article>
  `
};
