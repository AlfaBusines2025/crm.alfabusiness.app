@php
if(!isset($vendorViewBySuperAdmin))
$vendorViewBySuperAdmin = false;
@endphp
@if (hasCentralAccess() and !$vendorViewBySuperAdmin )
<div class="header pb-5 pt-2 pt-md-7">
    <div class="container-fluid">
        <div class="header-body" x-cloak x-data="{totalVendors:{{ $totalVendors }},totalActiveVendors:{{ $totalActiveVendors }},totalCampaigns:{{ $totalCampaigns }},messagesInQueue:{{ $messagesInQueue }},totalContacts:{{ $totalContacts }},totalMessagesProcessed:{{ $totalMessagesProcessed }} }">
            <!-- Card stats -->
            <div class="row">
                <div class="col-md-6 col-lg col-sm-12">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Vendors') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalVendors)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-store text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span>{{ __tr('Total Vendors in the system') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg col-sm-12">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Active Vendors') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalActiveVendors)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-store text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md col-sm-12">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Contacts') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalContacts)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-users text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-md-4">
                <div class="col-md-6 col-lg col-sm-12">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Campaigns') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalCampaigns)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-bullhorn text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg col-sm-12">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Messages in Queue') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(messagesInQueue)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-stream text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md col-sm-12">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Messages Processed') }}</h5>
                                    <span class="h2 font-weight-bold mb-0"
                                        x-text="__Utils.formatAsLocaleNumber(totalMessagesProcessed)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-tasks text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- show.dropdown.result --}}
@elseif(hasVendorAccess() or hasVendorUserAccess() or $vendorViewBySuperAdmin )
<div class="header">
    <div class="container-fluid">
        <div class="header-body">
            <!-- Card stats -->
            <div class="row">
                <div class="col-12">
					
                    <div class="row mb-2">
                        @if (hasVendorAccess('manage_contacts'))
                        {{-- total contacts --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Contacts') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalContacts) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-info text-white rounded-circle shadow">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.contact.read.list_view') }}">{{  __tr('Manage Contacts') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total contacts --}}
                        {{-- total groups --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Groups') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalGroups) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.contact.group.read.list_view') }}">{{  __tr('Manage Groups') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total groups --}}
                        @endif
                        @if (hasVendorAccess('manage_campaigns'))
                        {{-- total totalCampaigns --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Campaigns') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalCampaigns) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                                <i class="fa fa-bullhorn"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.campaign.read.list_view') }}">{{  __tr('Manage Campaigns') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total totalCampaigns --}}
                        @endif
                        @if (hasVendorAccess('manage_templates'))
                        {{-- total totalTemplates --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Templates') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalTemplates) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fa fa-layer-group"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.whatsapp_service.templates.read.list_view') }}">{{  __tr('Manage Templates') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total totalTemplates --}}
                        @endif
                        @if (hasVendorAccess('manage_bot_replies'))
                        {{-- total totalBotReplies --}}
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Bot Replies') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalBotReplies) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div
                                                class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fa fa-robot"></i>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$vendorViewBySuperAdmin)
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <a href="{{ route('vendor.bot_reply.read.list_view') }}">{{  __tr('Manage Bot Replies') }}</a>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- /total totalBotReplies --}}
                        @endif
                          {{-- total active team member --}}
                          @if (hasVendorAccess('administrative'))
                          <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                             <div class="card card-stats mb-4 mb-xl-0">
                                 <div class="card-body">
                                     <div class="row">
                                         <div class="col">
                                             <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Active Team Members') }}</h5>
                                             <span class="h2 font-weight-bold mb-0">{{ __tr($activeTeamMembers) }}</span>
                                         </div>
                                         <div class="col-auto">
                                             <div
                                                 class="icon icon-shape bg-warning text-white rounded-circle shadow">
                                                 <i class="fas fa-user-tie"></i>
                                             </div>
                                         </div>
                                     </div>
                                     @if(!$vendorViewBySuperAdmin)
                                     <p class="mt-3 mb-0 text-muted text-sm">
                                         <a href="{{ route('vendor.user.read.list_view') }}">{{  __tr('Manage Team Member') }}</a>
                                     </p>
                                     @endif
                                 </div>
                             </div>
                         </div>
                         @endif
                         {{-- /total active team member --}}
                          {{-- manage campaigns --}}
                        @if (hasVendorAccess('manage_campaigns'))
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-md-4">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Messages in Queue') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($messagesInQueue) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fas fa-stream text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        {{-- /manage campaigns --}}
                         {{-- Messaging Processed--}}
                        @if (hasVendorAccess('messaging'))
                        <div class="col-md-6 col-lg col-sm-12">
                            <div class="card card-stats mb-4 mb-xl-0">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Messages Processed') }}</h5>
                                            <span class="h2 font-weight-bold mb-0">{{ __tr($totalMessagesProcessed) }}</span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fas fa-tasks text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
						{{-- /Messaging Processed --}}
						{{-- Metrics Web --}}
						@if (strlen(getVendorSettings('vendor_webhook_endpoint')) > 1)
							@php
								// Obtenemos el endpoint configurado y lo limpiamos para quitar la parte '/wp-json...'
								$vendorEndpoint = getVendorSettings('vendor_webhook_endpoint');
								$cleanEndpoint = preg_replace('#/wp-json.*$#', '', $vendorEndpoint);
								$token = getVendorSettings('vendor_api_access_token');
								$uid_vendor = getVendorUid();
							@endphp
							<br>
							<h1>{{ __tr('Web and E-Commerce Metrics') }}</h1>
							<br>

							<div class="header pb-5 pt-2 pt-md-7" x-data="metrics()" x-init="getMetrics()">
							  <div class="container-fluid">
								<div class="header-body">
								  <!-- Estado de carga -->
								  <template x-if="loading">
									<div class="text-center">
									  <span>{{ __tr('Loading Metrics...') }}</span>
									</div>
								  </template>

								  <!-- Estado de error -->
								  <template x-if="error">
									<div class="text-center text-danger">
									  <span x-text="error"></span>
									</div>
								  </template>

								  <!-- Datos cargados -->
								  <template x-if="!loading && !error">
									<div>
									  <!-- Sección: Web Info -->
									  <div class="row">
										<!-- Posts -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Posts') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.web_info.posts"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-info text-white rounded-circle shadow">
													<i class="fas fa-newspaper"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Pages -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Pages') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.web_info.pages"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-info text-white rounded-circle shadow">
													<i class="fas fa-file-alt"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Categories -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Categories') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.web_info.categories"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
													<i class="fas fa-list"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Tags -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Tags') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.web_info.tags"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
													<i class="fas fa-tags"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
									  </div>
									  <!-- Sección: Comentarios y Visitas -->
									  <div class="row">
										<!-- Comentarios Aprobados -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Comments Approved') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.web_info.comments.approved"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-success text-white rounded-circle shadow">
													<i class="fas fa-check-circle"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Comentarios Pendientes -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Comments Pending') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.web_info.comments.pending"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
													<i class="fas fa-hourglass-half"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Total Visits -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Visits') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.web_info.visits.total"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-secondary text-white rounded-circle shadow">
													<i class="fas fa-eye"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Unique Visits -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Unique Visits') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.web_info.visits.unique"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-secondary text-white rounded-circle shadow">
													<i class="fas fa-user"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
									  </div>
									  <!-- Sección: Métricas Ecommerce -->
									  <div class="row">
										<!-- Orders -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Orders') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.ecommerce.orders"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
													<i class="fas fa-shopping-cart"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Total Sales -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Sales') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.ecommerce.total_sales"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-success text-white rounded-circle shadow">
													<i class="fas fa-dollar-sign"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Average Order -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Average Order') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.ecommerce.average_order"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-info text-white rounded-circle shadow">
													<i class="fas fa-calculator"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Conversion Rate -->
										<div class="col-md-3 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Conversion Rate') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="web.ecommerce.conversion_rate"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
													<i class="fas fa-percentage"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
									  </div>
									  <!-- Sección: Tracking -->
									  <div class="row">
										<!-- Registered Users -->
										<div class="col-md-6 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Registered Users') }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="tracking.registered_users_count"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
													<i class="fas fa-users"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
										<!-- Today's Users -->
										<div class="col-md-6 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr("Today's Users") }}</h5>
												  <span class="h2 font-weight-bold mb-0" x-text="tracking.today_users_count"></span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-info text-white rounded-circle shadow">
													<i class="fas fa-user-clock"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
									  </div>
									  <!-- Tarjeta extra: Comparación Tracking vs Visitas Web -->
									  <div class="row">
										<div class="col-md-6 col-sm-6">
										  <div class="card card-stats mb-4">
											<div class="card-body">
											  <div class="row align-items-center">
												<div class="col">
												  <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Tracking vs Web Visits') }}</h5>
												  <span class="h2 font-weight-bold mb-0">
													<span x-text="tracking.registered_users_count"></span> / 
													<span x-text="web.web_info.visits.total"></span>
													<small x-text="trackingVsVisits() + '%'"></small>
												  </span>
												</div>
												<div class="col-auto">
												  <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
													<i class="fas fa-chart-line"></i>
												  </div>
												</div>
											  </div>
											</div>
										  </div>
										</div>
									  </div>
									</div>
								  </template>
								</div>
							  </div>
							</div>

							<!-- Script de Alpine.js -->
							<script>
							  function metrics() {
								return {
								  loading: true,
								  error: null,
								  // Valores por defecto
								  web: {
									web_info: {
									  posts: 0,
									  pages: 0,
									  categories: 0,
									  tags: 0,
									  comments: { approved: 0, pending: 0 },
									  visits: { total: 0, unique: 0 }
									},
									ecommerce: {
									  orders: 0,
									  total_sales: 0,
									  average_order: 0,
									  conversion_rate: 0,
									  products: 0
									}
								  },
								  tracking: {
									registered_users_count: 0,
									today_users_count: 0
								  },
								  // Calcula el porcentaje de usuarios registrados (tracking) vs visitas totales
								  trackingVsVisits() {
									return (this.web.web_info.visits.total > 0)
									  ? Math.round((this.tracking.registered_users_count / this.web.web_info.visits.total) * 100)
									  : 0;
								  },
								  getMetrics() {
									// Construir la URL usando la variable limpia $cleanEndpoint
									const apiUrl = `{{ $cleanEndpoint }}/wp-json/alfabusiness/api/v1/metrics?token={{ $token }}&uid_vendor={{ $uid_vendor }}`;

									fetch(apiUrl)
									  .then(response => {
										if (!response.ok) {
										  throw new Error('Error en la respuesta de la API');
										}
										
										return response.json();
									  })
									  .then(data => {
										// Actualizamos los datos con la respuesta de la API
										console.log(data);
										this.web = data.web;
										this.tracking = data.tracking;
										this.loading = false;
									  })
									  .catch(err => {
										console.error('Error fetching metrics:', err);
										this.error = '{{ __tr("Error al cargar las métricas. Inténtalo nuevamente más tarde.") }}';
										this.loading = false;
									  });
								  }
								}
							  }
							</script>
						@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif