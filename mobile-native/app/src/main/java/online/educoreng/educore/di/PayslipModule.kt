package online.educoreng.educore.di

import android.content.Context
import com.squareup.moshi.Moshi
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton
import online.educoreng.educore.core.data.repository.DefaultProfileSelfServiceRepository
import online.educoreng.educore.core.data.repository.DefaultStaffPayslipRepository
import online.educoreng.educore.core.data.repository.ProfileSelfServiceRepository
import online.educoreng.educore.core.data.repository.StaffPayslipRepository
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.StaffPayslipApi

@Module
@InstallIn(SingletonComponent::class)
object PayslipModule {
    @Provides
    @Singleton
    fun provideStaffPayslipApi(factory: ApiClientFactory): StaffPayslipApi =
        factory.create(StaffPayslipApi::class.java)

    @Provides
    @Singleton
    fun provideStaffPayslipRepository(
        @ApplicationContext context: Context,
        api: StaffPayslipApi,
        moshi: Moshi,
    ): StaffPayslipRepository = DefaultStaffPayslipRepository(context, api, moshi)

    @Provides
    @Singleton
    fun provideProfileSelfServiceRepository(
        @ApplicationContext context: Context,
        api: EduCoreApi,
        moshi: Moshi,
    ): ProfileSelfServiceRepository = DefaultProfileSelfServiceRepository(context, api, moshi)
}
