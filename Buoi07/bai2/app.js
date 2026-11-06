const app = Vue.createApp({
  data() {
    return {
      sanPham: [
        {
          id: 1,
          ten: 'Laptop học tập',
          gia: 14500000,
          anh: 'assets/laptop.svg',
          moTa: 'Laptop 14 inch, bộ nhớ RAM 16 GB, SSD 512 GB; phù hợp học tập và lập trình.',
          soLuongTon: 5
        },
        {
          id: 2,
          ten: 'Tai nghe Bluetooth',
          gia: 790000,
          anh: 'assets/tai-nghe.svg',
          moTa: 'Tai nghe không dây, thiết kế gọn nhẹ, thời lượng nghe nhạc dài.',
          soLuongTon: 9
        },
        {
          id: 3,
          ten: 'Bàn phím cơ',
          gia: 1250000,
          anh: 'assets/ban-phim.svg',
          moTa: 'Bàn phím cơ 87 phím, có đèn nền, kết nối qua cổng USB-C.',
          soLuongTon: 0
        },
        {
          id: 4,
          ten: 'Chuột không dây',
          gia: 350000,
          anh: 'assets/chuot.svg',
          moTa: 'Chuột không dây 2.4 GHz, cảm biến quang học, kích thước vừa tay.',
          soLuongTon: 12
        }
      ],
      thongBaoMua: ''
    };
  },
  computed: {
    soSanPhamConHang() {
      return this.sanPham.filter((sp) => sp.soLuongTon > 0).length;
    }
  },
  methods: {
    xuLyMua(tenSanPham) {
      // Sự kiện demo: chỉ thông báo, không giả lập thay đổi tồn kho/đặt hàng.
      this.thongBaoMua = `Bạn vừa chọn mua “${tenSanPham}” (thao tác minh họa).`;
    }
  }
});

app.component('the-san-pham', window.TheSanPham);
app.mount('#app');
