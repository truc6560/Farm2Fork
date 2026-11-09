-- Chuyển các bảng trước đây dùng MyISAM sang InnoDB và bật khóa ngoại thật.
-- Chạy sau khi đã nạp dữ liệu và kiểm tra không còn bản ghi mồ côi.

ALTER TABLE `bien_dong_kho` ENGINE = InnoDB;
ALTER TABLE `so_luong_giao_nhan` ENGINE = InnoDB;
ALTER TABLE `tiep_nhan_nguyen_lieu` ENGINE = InnoDB;
ALTER TABLE `migrations` ENGINE = InnoDB;

ALTER TABLE `bien_dong_kho`
  ADD CONSTRAINT `fk_bien_dong_kho_lo`
    FOREIGN KEY (`lo_nong_san_id`) REFERENCES `lo_nong_san` (`lo_nong_san_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bien_dong_kho_sscc`
    FOREIGN KEY (`sscc_id`) REFERENCES `don_vi_dong_goi_sscc` (`sscc_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bien_dong_kho_kho`
    FOREIGN KEY (`kho_lanh_id`) REFERENCES `kho_lanh` (`kho_lanh_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bien_dong_kho_tai_khoan`
    FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoan_he_thong` (`tai_khoan_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bien_dong_kho_phieu`
    FOREIGN KEY (`phieu_giao_hang_id`) REFERENCES `phieu_giao_hang` (`phieu_giao_hang_id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `so_luong_giao_nhan`
  ADD CONSTRAINT `fk_so_luong_giao_nhan_chi_tiet`
    FOREIGN KEY (`chi_tiet_giao_nhan_id`) REFERENCES `chi_tiet_giao_nhan` (`chi_tiet_giao_nhan_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_so_luong_giao_nhan_lo`
    FOREIGN KEY (`lo_nong_san_id`) REFERENCES `lo_nong_san` (`lo_nong_san_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `tiep_nhan_nguyen_lieu`
  ADD CONSTRAINT `fk_tiep_nhan_lo`
    FOREIGN KEY (`lo_nong_san_id`) REFERENCES `lo_nong_san` (`lo_nong_san_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tiep_nhan_to_chuc_giao`
    FOREIGN KEY (`to_chuc_giao_id`) REFERENCES `to_chuc` (`to_chuc_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tiep_nhan_to_chuc_nhan`
    FOREIGN KEY (`to_chuc_nhan_id`) REFERENCES `to_chuc` (`to_chuc_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tiep_nhan_nguoi_nhan`
    FOREIGN KEY (`nguoi_nhan_id`) REFERENCES `tai_khoan_he_thong` (`tai_khoan_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE;
