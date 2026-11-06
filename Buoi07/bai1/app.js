const { createApp } = Vue;

createApp({
  data() {
    return {
      sanPham: [
        { id: 1, ten: 'Chuột không dây', gia: 250000, danhMuc: 'Điện tử', tonKho: 12 },
        { id: 2, ten: 'Bàn phím cơ', gia: 950000, danhMuc: 'Điện tử', tonKho: 0 },
        { id: 3, ten: 'Sách học Vue 3', gia: 150000, danhMuc: 'Sách', tonKho: 8 },
        { id: 4, ten: 'Sổ tay lập trình', gia: 45000, danhMuc: 'Sách', tonKho: 20 },
        { id: 5, ten: 'Bút bi xanh', gia: 5000, danhMuc: 'Văn phòng phẩm', tonKho: 50 },
        { id: 6, ten: 'Bộ bút highlight', gia: 65000, danhMuc: 'Văn phòng phẩm', tonKho: 7 }
      ],
      danhMucSanPham: ['Điện tử', 'Sách', 'Văn phòng phẩm'],
      tuKhoa: '',
      danhMucLoc: '',
      kieuSapXep: 'mac-dinh',
      thongBaoTimKiem: '',
      thongBaoThem: '',
      loiThem: false,
      trangThai: 'Đang khởi tạo...',
      idKeTiep: 7,
      sanPhamMoi: { ten: '', gia: '', danhMuc: '', tonKho: '' }
    };
  },

  computed: {
    sanPhamHienThi() {
      const tuCanTim = this.tuKhoa.trim().toLocaleLowerCase('vi-VN');

      // filter() trả về mảng mới, không thay đổi this.sanPham.
      const ketQua = this.sanPham.filter((sp) => {
        const hopTen = sp.ten.toLocaleLowerCase('vi-VN').includes(tuCanTim);
        const hopDanhMuc = !this.danhMucLoc || sp.danhMuc === this.danhMucLoc;
        return hopTen && hopDanhMuc;
      });

      // Chỉ sắp xếp mảng mới, vì .sort() tác động trực tiếp lên mảng được gọi.
      if (this.kieuSapXep === 'tang') {
        ketQua.sort((a, b) => a.gia - b.gia);
      } else if (this.kieuSapXep === 'giam') {
        ketQua.sort((a, b) => b.gia - a.gia);
      }
      return ketQua;
    },

    tongGiaTriTonKho() {
      // tính trên sanPhamHienThi, KHÔNG phải toàn bộ sanPham.
      return this.sanPhamHienThi.reduce((tong, sp) => tong + sp.gia * sp.tonKho, 0);
    }
  },

  watch: {
    tuKhoa(tuKhoaMoi) {
      const doDai = tuKhoaMoi.trim().length;
      this.thongBaoTimKiem = doDai === 1
        ? 'Bạn đang nhập từ khóa chỉ có 1 ký tự. Hãy nhập thêm để tìm chính xác hơn.'
        : '';
    }
  },

  methods: {
    dinhDangTien(soTien) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency', currency: 'VND', maximumFractionDigits: 0
      }).format(soTien);
    },

    datLaiBoLoc() {
      this.tuKhoa = '';
      this.danhMucLoc = '';
      this.kieuSapXep = 'mac-dinh';
    },

    themSanPham() {
      const ten = this.sanPhamMoi.ten.trim();
      const gia = this.sanPhamMoi.gia;
      const tonKho = this.sanPhamMoi.tonKho;
      const danhMuc = this.sanPhamMoi.danhMuc;
      this.loiThem = true;

      if (!ten) {
        this.thongBaoThem = 'Tên sản phẩm không được để trống.';
        return;
      }
      if (gia === '' || typeof gia !== 'number' || !Number.isFinite(gia) || gia <= 0) {
        this.thongBaoThem = 'Đơn giá phải là số lớn hơn 0.';
        return;
      }
      if (tonKho === '' || typeof tonKho !== 'number' || !Number.isInteger(tonKho) || tonKho < 0) {
        this.thongBaoThem = 'Tồn kho phải là số nguyên không âm (0, 1, 2, ...).';
        return;
      }
      if (!this.danhMucSanPham.includes(danhMuc)) {
        this.thongBaoThem = 'Vui lòng chọn một danh mục hợp lệ.';
        return;
      }

      this.sanPham.push({ id: this.idKeTiep++, ten, gia, danhMuc, tonKho });
      this.loiThem = false;
      this.thongBaoThem = `Đã thêm sản phẩm “${ten}” thành công.`;
      this.sanPhamMoi = { ten: '', gia: '', danhMuc: '', tonKho: '' };
    },

    xoaSanPham(id) {
      const sanPhamCanXoa = this.sanPham.find((sp) => sp.id === id);
      if (!sanPhamCanXoa) return;

      // Người dùng phải xác nhận trước khi thực hiện thao tác xóa.
      if (!window.confirm(`Bạn có chắc muốn xóa sản phẩm “${sanPhamCanXoa.ten}” không?`)) return;
      this.sanPham = this.sanPham.filter((sp) => sp.id !== id);
      this.loiThem = false;
      this.thongBaoThem = `Đã xóa sản phẩm “${sanPhamCanXoa.ten}”.`;
    }
  },

  mounted() {
    this.trangThai = 'Sẵn sàng';
  }
}).mount('#app');
