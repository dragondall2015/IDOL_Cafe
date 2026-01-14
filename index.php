<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>생일카페 캘린더</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <a href="register.php">회원가입</a>
    <div class="container">
        <div class="header">
            <h1>생일카페 캘린더</h1>
            
            <div class="calendar-nav">
                <button class="nav-btn" id="prevMonth">‹</button>
                <div class="month-year" id="monthYear"></div>
                <button class="nav-btn" id="nextMonth">›</button>
                <div class="calendar-controls">
                    <button class="settings-btn">⚙</button>
                    <button class="info-btn">?</button>
                </div>
            </div>
        </div>

        <div class="calendar-section">
            <div class="weekdays">
                <div class="weekday">일</div>
                <div class="weekday">월</div>
                <div class="weekday">화</div>
                <div class="weekday">수</div>
                <div class="weekday">목</div>
                <div class="weekday">금</div>
                <div class="weekday">토</div>
            </div>
            
            <div class="calendar-grid" id="calendarGrid"></div>
        </div>

        <!-- Poster Section -->
        <div class="poster-section" id="posterSection" style="display: none;">
            <img id="posterImage" class="poster-image" alt="생일카페 포스터">
            <div class="poster-caption">생일카페 포스터</div>
        </div>

        <div class="bottom-section">
            <div class="add-event-text" id="addEventBtn">🎉 생일카페 등록하기</div>
            
            <div class="info-text">
                누구나 무료로 생일카페를 등록 및 확인할 수 있습니다.<br>
            </div>
            
            <div class="powered-by">Powered by 캡스톤디자인5조</div>
        </div>

        <div class="event-display" id="eventDisplay" style="display: none;">
            <h4>선택한 날짜의 행사</h4>
            <div id="eventList"></div>
        </div>
    </div>

    <!-- Add Event Form Modal -->
    <div class="add-event-form" id="addEventForm">
        <div class="form-container">
            <div class="form-header">
                <h3>🎨 새 행사 등록</h3>
            </div>
            
            <div class="form-group">
                <label>행사명</label>
                <input type="text" id="eventName" placeholder="예: 아이유 생일카페">
            </div>
            
            <div class="form-group">
                <label>장소</label>
                <input type="text" id="eventLocation" placeholder="예: 스타벅스 강남점">
            </div>
            
            <div class="form-group">
                <label>날짜</label>
                <input type="date" id="eventDate">
            </div>
            
            <div class="form-group time-range-group">
                <label for="eventTimeStart">운영 시간</label>
                <div class="time-range-inputs">
                    <input type="time" id="eventTimeStart" required>
                    <span class="time-separator">~</span>
                    <input type="time" id="eventTimeEnd" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="eventAddress">주소</label>
                <input type="text" id="eventAddress" placeholder="주소 검색 클릭" readonly onclick="searchAddress()" required>
            </div>
            <div class="form-group">
                <label for="eventAddressDetail">상세 주소</label>
                <input type="text" id="eventAddressDetail" placeholder="예: 3층 302호">
            </div>
                        
            <div class="form-group">
                <label>설명</label>
                <textarea id="eventDescription" rows="3" placeholder="행사 상세 정보"></textarea>
            </div>
            
            <div class="form-group">
                <label>포스터 이미지</label>
                <input type="file" id="eventPoster" accept="image/*">
                <div class="file-preview" id="filePreview" style="display: none;">
                    <img id="previewImage" style="max-width: 100px; max-height: 100px; border-radius: 6px; margin-top: 10px;">
                </div>
            </div>
            
            <div class="form-buttons">
                <button class="btn btn-secondary" id="cancelBtn">취소</button>
                <button class="btn btn-primary" id="saveBtn">저장</button>
            </div>
        </div>
    </div>

    <!-- Toast Message -->
    <div class="toast" id="toast"></div>
    <script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
    <script>
        class MobileCalendar {
            constructor() {
                this.currentDate = new Date();
                this.selectedDate = null;
                this.events = this.loadEvents();
                this.uploadedImageUrl = null;
                this.init();
                this.loadPosterImage();
                this.loadEventsFromServer();
            }

            init() {
                this.renderCalendar();
                this.bindEvents();
            }

            async loadPosterImage() {
                try {
                    if (window.fs && window.fs.readFile) {
                        const possibleNames = [
                            '생일카페 포스터 1.jpg', 
                            'image.jpg', 
                            'image.png',
                            'poster.jpg'
                        ];
                        
                        for (const fileName of possibleNames) {
                            try {
                                const imageData = await window.fs.readFile(fileName);
                                const blob = new Blob([imageData], { type: 'image/jpeg' });
                                const imageUrl = URL.createObjectURL(blob);
                                
                                // 메인 포스터 섹션에 표시
                                const posterImage = document.getElementById('posterImage');
                                const posterSection = document.getElementById('posterSection');
                                
                                posterImage.src = imageUrl;
                                posterSection.style.display = 'block';
                                
                                // 업로드된 이미지 URL 저장
                                this.uploadedImageUrl = imageUrl;
                                
                                // 기존 이벤트들에도 이미지 적용
                                this.updateExistingEventsWithImage(imageUrl);
                                
                                console.log('포스터 이미지 로드 성공:', fileName);
                                break;
                            } catch (e) {
                                continue;
                            }
                        }
                    }
                } catch (error) {
                    console.log('포스터 이미지를 불러올 수 없습니다:', error);
                }
            }

            updateExistingEventsWithImage(imageUrl) {
                // 기존 이벤트들에 이미지 추가
                Object.keys(this.events).forEach(dateKey => {
                    this.events[dateKey].forEach(event => {
                        if (!event.poster) {
                            event.poster = imageUrl;
                        }
                    });
                });
                
                // 현재 선택된 날짜가 있다면 이벤트 다시 표시
                if (this.selectedDate) {
                    this.displayEvents(this.selectedDate);
                }
            }

            bindEvents() {
                document.getElementById('prevMonth').addEventListener('click', () => {
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                    this.renderCalendar();
                });

                document.getElementById('nextMonth').addEventListener('click', () => {
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                    this.renderCalendar();
                });

                document.getElementById('addEventBtn').addEventListener('click', () => {
                        this.showEventForm();
                });

                document.getElementById('saveBtn').addEventListener('click', () => {
                    this.saveEvent();
                });

                document.getElementById('cancelBtn').addEventListener('click', () => {
                    this.hideEventForm();
                });

                document.getElementById('addEventForm').addEventListener('click', (e) => {
                    if (e.target.id === 'addEventForm') {
                        this.hideEventForm();
                    }
                });

                document.getElementById('eventPoster').addEventListener('change', (e) => {
                    this.handleFilePreview(e);
                });
            }

            renderCalendar() {
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                
                document.getElementById('monthYear').textContent = `${year}년 ${month + 1}월`;

                const firstDay = new Date(year, month, 1).getDay();
                const lastDate = new Date(year, month + 1, 0).getDate();
                const prevLastDate = new Date(year, month, 0).getDate();

                let daysHTML = '';

                // 이전 달 마지막 날들
                for (let i = firstDay - 1; i >= 0; i--) {
                    daysHTML += `<div class="day empty">${prevLastDate - i}</div>`;
                }

                // 현재 달 날짜들
                for (let day = 1; day <= lastDate; day++) {
                    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const hasEvent = this.events[dateStr] && this.events[dateStr].length > 0;
                    const isToday = this.isToday(year, month, day);
                    
                    daysHTML += `
                        <div class="day ${hasEvent ? 'has-event' : ''} ${isToday ? 'today' : ''}" 
                             data-date="${dateStr}">
                            ${day}
                        </div>
                    `;
                }

                // 다음 달 첫 날들
                const totalCells = Math.ceil((firstDay + lastDate) / 7) * 7;
                const remainingCells = totalCells - (firstDay + lastDate);
                for (let day = 1; day <= remainingCells; day++) {
                    daysHTML += `<div class="day empty">${day}</div>`;
                }

                document.getElementById('calendarGrid').innerHTML = daysHTML;

                // 날짜 클릭 이벤트 추가
                document.querySelectorAll('.day:not(.empty)').forEach(day => {
                    day.addEventListener('click', () => {
                        this.selectDate(day.dataset.date);
                    });
                });
            }

            isToday(year, month, day) {
                const today = new Date();
                return year === today.getFullYear() && 
                       month === today.getMonth() && 
                       day === today.getDate();
            }

            selectDate(dateStr) {
                document.querySelectorAll('.day.selected').forEach(el => {
                    el.classList.remove('selected');
                });

                document.querySelector(`[data-date="${dateStr}"]`).classList.add('selected');
                this.selectedDate = dateStr
                this.displayEvents(dateStr);
            }

            displayEvents(dateStr) {
                const eventDisplay = document.getElementById('eventDisplay');
                const eventList = document.getElementById('eventList');
                const events = this.events[dateStr] || [];

                if (events.length === 0) {
                    eventDisplay.style.display = 'none';
                    return;
                }

                let html = '';
                events.forEach(event => {
                    html += `
                        <a href="event-detail.php?id=${event.id}" class="event-item-link">
                            <div class="event-item">
                                ${event.poster ? `<img src="${event.poster}" class="event-poster" alt="행사 포스터">` : ''}
                                <div class="event-content">
                                    <div class="event-title">🎨 ${event.name}</div>
                                    <div class="event-details">
                                        <strong>장소:</strong> ${event.location}<br>
                                        <strong>시간:</strong> ${event.start_time} ~ ${event.end_time}<br>
                                        <strong>주소:</strong> ${event.address}<br>
                                        <strong>설명:</strong> ${event.description}
                                    </div>
                                </div>
                                <div style="clear: both;"></div>
                            </div>
                        </a>
                    `;
                });

                eventList.innerHTML = html;
                eventDisplay.style.display = 'block';
            }

            showEventForm() {
                document.getElementById('eventDate').value = this.selectedDate;
                document.getElementById('addEventForm').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            hideEventForm() {
                document.getElementById('addEventForm').style.display = 'none';
                document.body.style.overflow = 'auto';
                this.clearForm();
            }

            saveEvent() {
                const name = document.getElementById('eventName').value;
                const location = document.getElementById('eventLocation').value;
                const eventDate = document.getElementById('eventDate').value;
                const startTime = document.getElementById('eventTimeStart').value;
                const endTime = document.getElementById('eventTimeEnd').value;
                const addressMain = document.getElementById('eventAddress').value;
                const addressDetail = document.getElementById('eventAddressDetail')?.value || '';
                const fullAddress = `${addressMain} ${addressDetail}`.trim();
                const description = document.getElementById('eventDescription').value;
                const posterFile = document.getElementById('eventPoster').files[0];

                if (!name || !location || !eventDate || !startTime || !endTime) {
                    this.showToast('행사명, 장소, 날짜, 시간은 필수입니다!');
                    return;
                }

                const formData = new FormData();
                formData.append("name", name);
                formData.append("location", location);
                formData.append("eventDate", eventDate);
                formData.append("start_time", startTime);
                formData.append("end_time", endTime);
                formData.append("address", fullAddress);
                formData.append("description", description);
                if (posterFile) {
                    formData.append("poster", posterFile);
                }

                fetch("save_event.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.showToast("행사가 성공적으로 저장되었습니다!");
                        this.hideEventForm();
                        this.clearForm();
                        this.loadEventsFromServer(); // ✅ 여기 중요!
                    } else {
                        this.showToast("저장 실패: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    this.showToast("저장 중 오류가 발생했습니다.");
                });
            }


            addEventToCalendar(eventDate, newEvent) {
                if (!this.events[eventDate]) {
                    this.events[eventDate] = [];
                }

                this.events[eventDate].push(newEvent);
                this.saveEvents();
                this.hideEventForm();

                this.showToast('행사가 성공적으로 등록되었습니다! 🎉');

                // ✅ 강제로 즉시 새로고침
                location.reload();
            }


            handleFilePreview(event) {
                const file = event.target.files[0];
                const preview = document.getElementById('filePreview');
                const previewImage = document.getElementById('previewImage');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImage.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            }

            clearForm() {
                document.getElementById('eventName').value = '';
                document.getElementById('eventLocation').value = '';
                document.getElementById('eventDate').value = '';
                document.getElementById('eventTimeStart').value = '';  // ✅ 수정
                document.getElementById('eventTimeEnd').value = '';    // ✅ 수정
                document.getElementById('eventAddress').value = '';
                const addrDetail = document.getElementById('eventAddressDetail');
                if (addrDetail) addrDetail.value = '';  // 있으면 초기화
                document.getElementById('eventDescription').value = '';
                document.getElementById('eventPoster').value = '';
                document.getElementById('filePreview').style.display = 'none';
            }

            showToast(message) {
                const toast = document.getElementById('toast');
                toast.textContent = message;
                toast.style.display = 'block';
                
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 3000);
            }

            loadEvents() {
                return {
                };
            }

            saveEvents() {
                console.log('이벤트 저장됨:', this.events);
            }

            async loadEventsFromServer() {
                try {
                    const res = await fetch("load_events.php?ts=" + Date.now()); // ✅ 캐시 방지
                    const data = await res.json();

                    if (data.success) {
                        this.events = data.events;
                        this.renderCalendar();
                    } else {
                        this.showToast("이벤트 불러오기 실패: " + data.message);
                    }
                } catch (e) {
                    console.error("불러오기 오류:", e);
                    this.showToast("불러오기 오류 발생");
                }
            }
        }

        function searchAddress() { //주소 api
            new daum.Postcode({
                oncomplete: function(data) {
                    document.getElementById('eventAddress').value = data.address;
                }
            }).open();
        }
        const calendar = new MobileCalendar();

    </script>
</body>
</html>