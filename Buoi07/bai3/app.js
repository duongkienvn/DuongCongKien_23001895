const DU_LIEU_MAU = [
  { id: 1, ten: 'Bút bi', donGia: 5000, soLuong: 2 },
  { id: 2, ten: 'Quyển vở', donGia: 12000, soLuong: 3 }
];

const app = Vue.createApp({
  data() {
    return {
      gioHang: DU_LIEU_MAU.map((sp) => ({ ...sp })),
      thongBaoLoi: ''
    };
  },

  computed: {
    tongSoLuong() {
      return this.gioHang.reduce((tong, sp) => tong + sp.soLuong, 0);
    },
    tongTien() {
      return this.gioHang.reduce((tong, sp) => tong + sp.donGia * sp.soLuong, 0);
    }
  },

  methods: {
    dinhDangTien(soTien) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency', currency: 'VND', maximumFractionDigits: 0
      }).format(soTien);
    },

    doiSoLuong(id, soLuongMoi) {
      this.thongBaoLoi = '';
      if (!Number.isInteger(soLuongMoi) || soLuongMoi < 1 || soLuongMoi > 99) {
        this.thongBaoLoi = 'Số lượng chỉ được là số nguyên từ 1 đến 99.';
        return;
      }
      const sanPham = this.gioHang.find((sp) => sp.id === id);
      if (!sanPham) {
        this.thongBaoLoi = 'Không tìm thấy sản phẩm trong giỏ hàng.';
        return;
      }
      sanPham.soLuong = soLuongMoi;
    },

    xoaSanPham(id) {
      this.thongBaoLoi = '';
      if (!this.gioHang.some((sp) => sp.id === id)) {
        this.thongBaoLoi = 'Không tìm thấy sản phẩm cần xóa.';
        return;
      }
      this.gioHang = this.gioHang.filter((sp) => sp.id !== id);
    },

    khoiPhucDuLieuMau() {
      this.gioHang = DU_LIEU_MAU.map((sp) => ({ ...sp }));
      this.thongBaoLoi = '';
    }
  }
});

app.component('dong-gio-hang', window.DongGioHang);
app.mount('#app');
